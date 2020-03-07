<?php

namespace App\Http\Controllers\Api;

use App\Models\UsersFriends;
use Illuminate\Http\Request;
use App\Helpers\Cache\CacheHelper;
use App\Facades\WebSocketHelper;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\UsersChatFiles;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;
use App\Models\EmoticonDetails;
use App\Logic\ChatLogic;
use App\Logic\UsersLogic;

class ChatController extends CController
{
    public $request;
    public $chatLogic;

    public function __construct(Request $request, ChatLogic $chatLogic)
    {
        $this->request = $request;
        $this->chatLogic = $chatLogic;
    }

    /**
     * 获取用户聊天列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatList()
    {
        $rows = $this->chatLogic->getUserChatList($this->uid());
        if ($rows) {
            $rows = arraysSort($rows, 'created_at');
        }

        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 获取用户聊天指定的列表信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatItem()
    {
        $type = $this->request->get('type', 0);
        $receive_id = $this->request->get('receive_id', 0);
        $uid = $this->uid();
        if (!in_array($type, [1, 2]) || !isInt($receive_id)) {
            return $this->ajaxParamError();
        }

        $item = [
            'type' => $type,
            'name' => '',
            'unread_num' => 1,
            'not_disturb' => 0,
            'group_members_num' => 0,
            'avatar' => '',
            'remark_name' => '',
            'msg_text' => '......',
            'friend_id' => 0,
            'group_id' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];


        if ($type == 1) {
            $friendInfo = User::select(['nickname', 'avatarurl'])->where('id', $receive_id)->first();
            if (!$friendInfo) {
                return $this->ajaxError('获取列表信息失败...');
            }
            $item['name'] = $friendInfo->nickname;
            $item['avatar'] = $friendInfo->avatarurl;
            unset($friendInfo);

            $info = UsersFriends::select(['user1', 'user1_remark', 'user2_remark'])->where('user1', $uid < $receive_id ? $uid : $receive_id)->where('user2', $uid > $receive_id ? $uid : $receive_id)->where('status', 1)->first();
            if (!$info) {
                return $this->ajaxError('获取列表信息失败...');
            }

            $item['remark_name'] = $uid == $info->user1 ? $info->user1_remark : $info->user2_remark;
            unset($info);

            $item['unread_num'] = intval(CacheHelper::getChatUnreadNum($uid, $receive_id));
            $item['friend_id'] = $receive_id;
        } else {
            $groupInfo = UsersGroup::select(['group_name', 'avatarurl'])->where('id', $receive_id)->where('status', 0)->first();
            if (!$groupInfo) {
                return $this->ajaxError('获取列表信息失败...');
            }
            $item['name'] = $groupInfo->group_name;
            $item['avatar'] = $groupInfo->avatarurl;
            unset($groupInfo);

            $groupMemberInfo = UsersGroupMember::select(['not_disturb'])->where('group_id', $receive_id)->where('user_id', $uid)->where('status', 0)->first();
            if (!$groupMemberInfo) {
                return $this->ajaxError('获取列表信息失败...');
            }

            $item['not_disturb'] = $groupMemberInfo->not_disturb;
            unset($groupMemberInfo);

            $item['group_id'] = $receive_id;
        }


        $id = $this->chatLogic->createChatList($uid, $receive_id, $type);
        if ($id == 0) {
            return $this->ajaxError('获取列表信息失败...');
        }

        $item['id'] = $id;

        $records = CacheHelper::getLastChatCache($receive_id, $type == 1 ? $uid : 0);
        if ($records) {
            $item['msg_text'] = $records['text'];
            $item['created_at'] = $records['send_time'];
        }

        return $this->ajaxSuccess('success', $item);
    }

    /**
     * 获取私信或群聊的聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatRecords()
    {
        $record_id = $this->request->get('record_id', 0);
        $receive_id = $this->request->get('receive_id', 0);
        $type = $this->request->get('type', 1);
        $page_size = 20;
        if (!isInt($record_id, true) || !isInt($receive_id) || !in_array($type, [1, 2])) {
            return $this->ajaxParamError();
        }

        $uid = $this->uid();
        $data = $type == 1 ? $this->chatLogic->getPrivateChatInfos($record_id, $uid, $receive_id, $page_size) : $this->chatLogic->getGroupChatInfos($record_id, $receive_id, $uid, $page_size);
        $data['page_size'] = $page_size;

        if (count($data['rows']) > 0) {
            $data['rows'] = array_map(function ($item) use ($uid) {
                $item['fileInfo'] = [];
                $item['float'] = ($item['user_id'] == 0) ? 'center' : ($item['user_id'] == $uid ? 'right' : 'left');

                //消息类型处理
                switch ($item['msg_type']) {
                    case 1://文字消息
                        $item['content'] = emojiReplace($item['content']);
                        break;
                    case 2://文件消息
                        $fileInfo = UsersChatFiles::where('id', $item['file_id'])->first(['file_type', 'file_suffix', 'file_size', 'save_dir', 'original_name']);
                        if ($fileInfo) {
                            $item["fileInfo"]['file_type'] = $fileInfo->file_type;
                            $item["fileInfo"]['file_suffix'] = $fileInfo->file_suffix;
                            $item["fileInfo"]['file_size'] = $fileInfo->file_size;
                            $item["fileInfo"]['original_name'] = $fileInfo->original_name;
                            $item["fileInfo"]['url'] = $fileInfo->file_type == 1 ? getFileUrl($fileInfo->save_dir) : '';
                        }
                        unset($fileInfo);
                        break;
                    case 3://系统入群消息
                    case 4://系统退群消息
                        $uids = explode(',', $item['content']);
                        $item['content'] = customSort(User::select('id', 'nickname')->whereIn('id', $uids)->get()->toArray(), $uids);
                        break;
                }

                unset($item['file_id']);
                return $item;
            }, $data['rows']);
        }

        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 获取私信或群聊的聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatFiles()
    {
        $page = $this->request->get('page', 1);
        $page_size = $this->request->get('page_size', 15);
        $receive_id = $this->request->get('receive_id', 0);
        $type = $this->request->get('type', 1);

        $data = $this->chatLogic->getChatFiles($this->uid(), $receive_id, $type, $page, $page_size);
        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 创建群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function launchGroupChat()
    {
        $group_avatar = $this->request->post('group_avatar', '');
        $group_name = $this->request->post('group_name', '');
        $group_profile = $this->request->post('group_profile', '');
        $uids = $this->request->post('uids', '');

        if (empty($group_name) || empty($uids)) {
            return $this->ajaxParamError();
        }

        $uids = array_filter(explode(',', $uids));
        if (!checkIds($uids)) {
            return $this->ajaxParamError();
        }

        [$isTrue, $data] = $this->chatLogic->launchGroupChat($this->uid(), $group_name, $group_avatar, $group_profile, array_unique($uids));
        if ($isTrue) {//群聊创建成功后需要创建聊天室并发送消息通知
            foreach ($data['uids'] as $uuid) {
                WebSocketHelper::bindUserGroupChat($uuid, $data['group_info']['id']);
            }

            //推送退群消息
            WebSocketHelper::sendResponseMessage('join_group', WebSocketHelper::getRoomGroupName($data['group_info']['id']), [
                'message' => [
                    'avatar' => '',
                    'send_user' => 0,
                    'receive_user' => $data['group_info']['id'],
                    'source_type' => 2,
                    'msg_type' => 3,
                    'content' => customSort(User::select('id', 'nickname')->whereIn('id', $data['uids'])->get()->toArray(), $data['uids']),
                    'send_time' => date('Y-m-d H:i:s'),
                    'sendUserInfo' => []
                ],
                'group_info' => UsersGroup::select(['id', 'group_name', 'people_num', 'avatarurl'])->where('id', $data['group_info']['id'])->first()->toArray()
            ]);

            return $this->ajaxSuccess('创建群聊成功...', $data);
        }

        return $this->ajaxError('创建群聊失败，请稍后再试...');
    }

    /**
     * 邀请好友加入群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteGroupChat()
    {
        $group_id = $this->request->post('group_id', 0);
        $uids = array_filter(explode(',', $this->request->post('uids', '')));

        if (!isInt($group_id) || !checkIds($uids)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->inviteFriendsGroupChat($this->uid(), $group_id, $uids);
        if ($isTrue) {
            foreach ($uids as $uuid) {
                WebSocketHelper::bindUserGroupChat($uuid, $group_id);
            }

            $userInfo = $this->getUser(true);

            $users = [
                ['id' => $userInfo['id'], 'nickname' => $userInfo['nickname']]
            ];

            //推送入群消息
            WebSocketHelper::sendResponseMessage('join_group', WebSocketHelper::getRoomGroupName($group_id), [
                'message' => [
                    'avatar' => '',
                    'send_user' => 0,
                    'receive_user' => $group_id,
                    'source_type' => 2,
                    'msg_type' => 3,
                    'content' => array_merge($users, User::select('id', 'nickname')->whereIn('id', $uids)->get()->toArray()),
                    'send_time' => date('Y-m-d H:i:s'),
                    'sendUserInfo' => []
                ],
                'group_info' => UsersGroup::select(['id', 'group_name', 'people_num', 'avatarurl'])->where('id', $group_id)->first()->toArray()
            ]);
        }

        return $isTrue ? $this->ajaxSuccess('好友已成功加入群聊...') : $this->ajaxError('邀请好友加入群聊失败...');
    }

    /**
     * 用户踢出群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeGroupChat()
    {
        $group_id = $this->request->post('group_id', 0);
        $member_id = $this->request->post('member_id', 0);

        if (!isInt($group_id) || !isInt($member_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->removeGroupChat($group_id, $this->uid(), $member_id);
        if ($isTrue) {
            //将用户移出聊天室
            WebSocketHelper::quitGroupRoom($member_id, $group_id);

            $user = $this->getUser();
            $message = [
                'msg_type' => 4,
                'content' => [
                    [
                        'id' => $user['id'],
                        'nickname' => $user['nickname']
                    ],
                    [
                        'id' => $member_id,
                        'nickname' => User::where('id', $member_id)->value('nickname')
                    ]
                ],
                'receive_user' => $group_id,
                'send_user' => 0,
                'send_time' => date('Y-m-d H:i:s'),
                'source_type' => 2
            ];

            //推送退群消息
            WebSocketHelper::sendResponseMessage('chat_message', WebSocketHelper::getRoomGroupName($group_id), $message);
        }
        return $isTrue ? $this->ajaxSuccess('群聊用户已被移除..') : $this->ajaxError('群聊用户移除失败...');
    }

    /**
     * 解散群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismissGroupChat()
    {
        $group_id = $this->request->post('group_id', 0);
        if (!isInt($group_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->dismissGroupChat($group_id, $this->uid());
        return $isTrue ? $this->ajaxSuccess('群聊已解散成功..') : $this->ajaxError('群聊解散失败...');
    }

    /**
     * 创建用户聊天列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createChatList()
    {
        $type = $this->request->post('type', 1);//创建的类型
        $receive_id = $this->request->post('receive_id', 0);//接收者ID

        if (!in_array($type, [1, 2]) || !isInt($receive_id)) {
            return $this->ajaxParamError();
        }

        $id = $this->chatLogic->createChatList($this->uid(), $receive_id, $type);
        return $id ? $this->ajaxSuccess('创建成功...', ['list_id' => $id]) : $this->ajaxError('创建失败...');
    }

    /**
     * 获取聊天群信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupDetail()
    {
        $group_id = $this->request->get('group_id', 0);
        if (!isInt($group_id)) {
            return $this->ajaxParamError();
        }

        $data = $this->chatLogic->getGroupDetail($this->uid(), $group_id);
        return $this->ajaxSuccess('success', $data);
    }


    /**
     * 获取用户聊天好友
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatMember(UsersLogic $usersLogic)
    {
        $group_id = $this->request->get('group_id', 0);
        $firends = $usersLogic->getUserFriends($this->uid());
        if ($group_id > 0) {
            $ids = UsersGroupMember::getGroupMenberIds($group_id);
            if ($firends && $ids) {
                foreach ($firends as $k => $item) {
                    if (in_array($item->id, $ids)) {
                        unset($firends[$k]);
                    }
                }
            }
        }

        return $this->ajaxSuccess('success', $firends);
    }

    /**
     * 更新用户聊天信息未读数
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateChatUnreadNum()
    {
        $type = $this->request->get('type', 0);
        $receive = $this->request->get('receive', 0);
        if ($type == 1 && isInt($receive)) {
            CacheHelper::delChatUnreadNum($this->uid(), $receive);
        } else if ($type == 2 && isInt($receive)) {
            CacheHelper::delChatUnreadNum($this->uid(), $receive);
        }

        return $this->ajaxSuccess('success');
    }

    /**
     * 设置用户群名名片
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setGroupCard()
    {
        $group_id = $this->request->post('group_id', 0);
        $visit_card = $this->request->post('visit_card', '');

        if (!isInt($group_id) || empty($visit_card)) {
            return $this->ajaxParamError();
        }

        $isTrue = UsersGroupMember::where('group_id', $group_id)->where('user_id', $this->uid())->where('status', 0)->update(['visit_card' => $visit_card]);
        if ($isTrue) {
            $user = $this->getUser();
            CacheHelper::setUserGroupVisitCard($group_id, $this->uid(), [
                'avatar' => $user->avatarurl,
                'nickname' => $user->nickname,
                'visit_card' => $visit_card
            ]);
        }

        return $isTrue ? $this->ajaxSuccess('设置成功') : $this->ajaxError('设置失败');
    }

    /**
     * 用户退出群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function quitGroupChat()
    {
        $group_id = $this->request->post('group_id', 0);
        if (!isInt($group_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->quitGroupChat($group_id, $this->uid());
        if ($isTrue) {
            //将用户移出聊天室
            WebSocketHelper::quitGroupRoom($this->uid(), $group_id);

            $user = $this->getUser();
            $message = [
                'msg_type' => 6,
                'content' => [
                    [
                        'id' => $user['id'],
                        'nickname' => $user['nickname']
                    ]
                ],
                'receive_user' => $group_id,
                'send_user' => 0,
                'send_time' => date('Y-m-d H:i:s'),
                'source_type' => 2
            ];

            //推送退群消息
            WebSocketHelper::sendResponseMessage('chat_message', WebSocketHelper::getRoomGroupName($group_id), $message);
        }

        return $isTrue ? $this->ajaxSuccess('已成功退出群聊...') : $this->ajaxError('退出群聊失败...');
    }

    /**
     * 设置群聊免打扰状态接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setGroupDisturb()
    {
        $group_id = $this->request->post('group_id', 0);
        $status = $this->request->post('status', null);
        if (!isInt($group_id) || !in_array($status, [0, 1])) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->setGroupDisturb($this->uid(), $group_id, $status);
        return $isTrue ? $this->ajaxSuccess('设置成功...') : $this->ajaxError('设置失败...');
    }

    /**
     * 发送聊天图片
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage()
    {
        $file = $this->request->file('img');
        if (!$file->isValid()) {
            return $this->ajaxParamError('请求参数错误');
        }

        $ext = $file->getClientOriginalExtension();
        //图片格式验证
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->ajaxParamError('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        $imgInfo = getimagesize($file->getRealPath());
        $filename = getSaveImgName($ext, $imgInfo[0], $imgInfo[1]);

        //保存图片
        if (!$save_path = Storage::disk('uploads')->putFileAs('images/' . date('Ymd'), $file, $filename)) {
            return $this->ajaxError('图片上传失败');
        }

        $result = UsersChatFiles::create([
            'user_id' => $this->uid(),
            'file_type' => 1,
            'file_suffix' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'save_dir' => $save_path,
            'original_name' => $file->getClientOriginalName(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $result ? $this->ajaxSuccess('图片上传成功...', ['file_info' => encrypt($result->id)]) : $this->ajaxError('图片上传失败');
    }


    /**
     * 查询聊天记录
     */
    public function findChatRecords()
    {


        $user_id = $this->uid();
        $receive_id = $this->request->get('receive_id', 0);
        $source = $this->request->get('source', 1);
        $find_type = $this->request->get('find_type', 0);
        $find_mode = $this->request->get('find_mode', 0);
        $record_id = $this->request->get('record_id', 0);
        $limit = 30;


//        "min_record_id":1052,"max_record_id":1092
//        $user_id = 2054;
//        $receive_id = 4106;
//        $find_mode = 1;
//        $record_id = 1052;


        if (!isInt($receive_id) || !in_array($source, [1, 2]) || !in_array($find_type, [0, 1, 2]) || !in_array($find_mode, [0, 1, 2]) || !isInt($record_id, true)) {
            $this->ajaxParamError();
        }

        $result = $this->chatLogic->findChatRecords($user_id, $receive_id, $source, $find_type, $find_mode, $record_id,$limit);
        if ($result) {
            $result = array_map(function ($item) {
                $item['file_url'] = ($item['msg_type'] == 2) ? getFileUrl($item['save_dir']) : '';
                $item['content'] = emojiReplace($item['content']);
                return $item;
            }, $result);
        }

        return $this->ajaxSuccess('success', [
            'records' => $result,
            'min_record_id' => $result ? end($result)['id'] : 0,
            'max_record_id' => $result ? $result[0]['id'] : 0,
            'limit' => $limit,
            'count' => count($result),
        ]);
    }
}
