<?php

namespace App\Http\Controllers\Api;


use App\Models\User;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;
use Illuminate\Http\Request;
use App\Logic\ChatLogic;
use App\Facades\WebSocketHelper;
use App\Logic\UsersLogic;
use App\Helpers\Cache\CacheHelper;

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
        return $this->ajaxSuccess('success', $rows);
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
        if (count($data['rows']) > 0) {
            $data['rows'] = array_map(function ($item) use ($uid) {
                if ($item['user_id'] != 0) {
                    $item['float'] = $item['user_id'] == $uid ? 'right' : 'left';
                } else {
                    $item['float'] = 'center';
                }

                if ($item['msg_type'] == 1) {
                    $item['text_msg'] = emojiReplace($item['text_msg']);
                } else if (in_array($item['msg_type'], [5, 6])) {
                    $item['text_msg'] = User::select('id', 'nickname')->whereIn('id', explode(',', $item['text_msg']))->get()->toArray();
                }

                return $item;
            }, $data['rows']);
        }

        $data['page_size'] = $page_size;

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
            foreach ($data['group_info']['uids'] as $uuid) {
                WebSocketHelper::bindUserGroupChat($uuid, $data['group_info']['id']);
            }

            //推送退群消息
            WebSocketHelper::sendResponseMessage('join_group', WebSocketHelper::getRoomGroupName($data['group_info']['id']), [
                'message' => [
                    'avatar' => '',
                    'send_user' => 0,
                    'receive_user' => $data['group_info']['id'],
                    'source_type' => 2,
                    'msg_type' => 5,
                    'content' => User::select('id', 'nickname')->whereIn('id', $uids)->get()->toArray(),
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

            //推送退群消息
            WebSocketHelper::sendResponseMessage('join_group', WebSocketHelper::getRoomGroupName($group_id), [
                'message' => [
                    'avatar' => '',
                    'send_user' => 0,
                    'receive_user' => $group_id,
                    'source_type' => 2,
                    'msg_type' => 5,
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
            $date = date('Y-m-d H:i');
            $message = [
                'msg_type' => 1,
                'content' => "{$user['nickname']} 于{$date} 退出群聊",
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
}
