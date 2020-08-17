<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Logic\{ChatLogic};

use App\Models\{
    User,
    UsersChatRecordsForward,
    UsersFriends,
    UsersChatFiles,
    UsersGroup,
    UsersChatRecordsGroupNotify
};

use App\Helpers\Cache\CacheHelper;
use App\Helpers\RequestProxy;
use App\Helpers\Socket\ChatService;

/**
 * 聊天对话处理
 *
 * Class TalkController
 * @package App\Http\Controllers\Api
 */
class TalkController extends CController
{

    public $request;
    public $chatLogic;
    public $requestProxy;

    public function __construct(Request $request, ChatLogic $chatLogic, RequestProxy $requestProxy)
    {
        $this->request = $request;

        $this->chatLogic = $chatLogic;

        $this->requestProxy = $requestProxy;
    }

    /**
     * 新增对话列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $uid = $this->uid();
        $type = $this->request->post('type', 1);//创建的类型
        $receive_id = $this->request->post('receive_id', 0);//接收者ID

        if (!in_array($type, [1, 2]) || !isInt($receive_id)) {
            return $this->ajaxParamError();
        }

        if ($type == 1) {
            if (!UsersFriends::checkFriends($uid, $receive_id)) {
                return $this->ajaxReturn(305, '暂不属于好友关系，无法进行聊天...');
            }
        } else {
            if (!UsersGroup::checkGroupMember($receive_id, $uid)) {
                return $this->ajaxReturn(305, '暂不属于群成员，无法进行群聊 ...');
            }
        }

        $id = $this->chatLogic->createChatList($uid, $receive_id, $type);
        return $id ? $this->ajaxSuccess('创建成功...', ['list_id' => $id]) : $this->ajaxError('创建失败...');
    }

    /**
     * 删除对话列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete()
    {
        $list_id = $this->request->post('list_id', 0);

        if (!isInt($list_id)) return $this->ajaxParamError();

        $isTrue = $this->chatLogic->delChatList($this->uid(), $list_id);

        return $isTrue ? $this->ajaxSuccess('操作完成...') : $this->ajaxError('操作失败...');
    }

    /**
     * 对话列表置顶
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function topping()
    {
        $list_id = $this->request->post('list_id', 0);
        $type = $this->request->post('type', 0);

        if (!isInt($list_id) || !in_array($type, [1, 2])) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->chatListTop($this->uid(), $list_id, $type == 1);

        return $isTrue ? $this->ajaxSuccess('操作完成...') : $this->ajaxError('操作失败...');
    }

    /**
     * 设置消息免打扰状态
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setNotDisturb()
    {
        $type = $this->request->post('type', 0);
        $receive_id = $this->request->post('receive_id', 0);
        $not_disturb = $this->request->post('not_disturb', 0);

        if (!isInt($receive_id) || !in_array($type, [1, 2]) || !in_array($not_disturb, [0, 1])) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->setNotDisturb($this->uid(), $receive_id, $type, $not_disturb);

        return $isTrue ? $this->ajaxSuccess('设置成功...') : $this->ajaxError('设置失败...');
    }

    /**
     * 获取用户对话列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $rows = $this->chatLogic->getUserChatList($this->uid());
        if ($rows) {
            $rows = arraysSort($rows, 'updated_at');
        }

        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 更新对话列表未读数
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUnreadNum()
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
     * 获取对话面板中的聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatRecords()
    {
        $user_id = $this->uid();
        $receive_id = $this->request->get('receive_id', 0);
        $source = $this->request->get('source', 0);
        $record_id = $this->request->get('record_id', 0);
        $limit = 30;

        if (!isInt($receive_id) || !isInt($source) || !isInt($record_id, true)) {
            return $this->ajaxParamError();
        }

        //判断是否属于群成员
        if ($source == 2 && UsersGroup::checkGroupMember($receive_id, $user_id) == false) {
            return $this->ajaxSuccess('非群聊成员不能查看群聊信息', [
                'rows' => [],
                'record_id' => 0,
                'limit' => $limit
            ]);
        }

        if ($result = $this->chatLogic->getChatsRecords($user_id, $receive_id, $source, $record_id, $limit)) {
            //消息处理
            $result = array_map(function ($item) use ($user_id) {
                $item['float'] = $item['user_id'] == 0 ? 'center' : ($item['user_id'] == $user_id ? 'right' : 'left');
                $item['file_url'] = '';
                $item['friend_remarks'] = '';
                $item['forward_info'] = [];

                //消息类型处理
                switch ($item['msg_type']) {
                    case 1://文字消息
                        if ($item['is_code'] == 0) {
                            $item['content'] = replaceUrlToLink($item['content']);
                            $item['content'] = emojiReplace($item['content']);
                        } else {
                            $item['content'] = htmlspecialchars_decode($item['content']);
                        }

                        break;
                    case 2://文件消息
                        $item['file_url'] = ($item['msg_type'] == 2) ? getFileUrl($item['save_dir']) : '';
                        break;
                    case 3://系统入群/退群消息
                        $item['group_notify'] = [];
                        if ($info = UsersChatRecordsGroupNotify::where('record_id', $item['id'])->first(['type', 'operate_user_id', 'user_ids'])) {
                            $operateUser = User::select('id', 'nickname')->where('id', $info->operate_user_id)->first();
                            $item['group_notify'] = [
                                'type' => $info->type,
                                'operate_user' => ['id' => $operateUser->id, 'nickname' => $operateUser->nickname],
                                'users' => []
                            ];

                            if ($info->type == 1 || $info->type == 2) {
                                $item['group_notify']['users'] = User::select('id', 'nickname')->whereIn('id', explode(',', $info->user_ids))->get()->toArray();
                            } else if ($info->type == 3) {
                                $item['group_notify']['users'] = [$item['group_notify']['operate_user']];
                            }
                        }

                        break;
                    case 4://会话记录转发消息
                        $forwardInfo = UsersChatRecordsForward::where('id', $item['forward_id'])->first(['records_id', 'text']);
                        $item['forward_info'] = [
                            'num' => substr_count($forwardInfo->records_id, ',') + 1,
                            'list' => json_decode($forwardInfo->text, true)
                        ];
                        unset($forwardInfo);
                        break;
                }

                return $item;
            }, $result);
        }

        return $this->ajaxSuccess('success', [
            'rows' => $result,
            'record_id' => $result ? $result[count($result) - 1]['id'] : 0,
            'limit' => $limit
        ]);
    }

    /**
     * 撤回聊天对话消息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeChatRecords()
    {
        $user_id = $this->uid();
        $record_id = $this->request->get('record_id', 0);
        if (!isInt($record_id)) {
            return $this->ajaxParamError();
        }

        [$isTrue, $message, $data] = $this->chatLogic->revokeRecords($user_id, $record_id);
        if ($isTrue) {
            //这里需要调用WebSocket推送接口
            $this->requestProxy->send('proxy/event/revoke-records', [
                'record_id' => $data['id'],
                'source' => $data['source'],
                'user_id' => $data['user_id'],
                'receive_id' => $data['receive_id'],
            ]);
        }

        return $isTrue ? $this->ajaxSuccess($message) : $this->ajaxError($message);
    }

    /**
     * 删除聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeChatRecords()
    {
        $user_id = $this->uid();
        //消息来源（1：好友消息 2：群聊消息）
        $source = $this->request->post('source', 0);
        //接收者ID（好友ID或者群聊ID）
        $receive_id = $this->request->post('receive_id', 0);
        //消息ID
        $record_ids = explode(',', $this->request->get('record_id', ''));
        if (!in_array($source, [1, 2]) || !isInt($receive_id) || !checkIds($record_ids)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->removeRecords($user_id, $source, $receive_id, $record_ids);
        return $isTrue ? $this->ajaxSuccess('删除成功...') : $this->ajaxError('删除失败...');
    }

    /**
     * 转发聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forwardChatRecords()
    {
        $user_id = $this->uid();
        //转发方方式
        $forward_mode = $this->request->post('forward_mode', 0);
        //消息来源（1：好友消息 2：群聊消息）
        $source = $this->request->post('source', 1);
        //接收者ID（好友ID或者群聊ID）
        $receive_id = $this->request->post('receive_id', 0);
        //转发的记录IDS
        $records_ids = $this->request->post('records_ids', []);
        //转发的好友的ID
        $receive_user_ids = $this->request->post('receive_user_ids', []);
        //转发的群聊ID
        $receive_group_ids = $this->request->post('receive_group_ids', []);

        if (!in_array($forward_mode, [1, 2]) || !in_array($source, [1, 2]) || !isInt($receive_id) || !checkIds($records_ids) || !checkIds($receive_user_ids) || !checkIds($receive_group_ids)) {
            return $this->ajaxParamError();
        }

        if ($forward_mode == 1) {//逐条转发
            $ids = $this->chatLogic->forwardRecords($user_id, $source, $receive_id, $records_ids, $receive_user_ids, $receive_group_ids);
        } else {//合并转发
            $ids = $this->chatLogic->mergeForwardRecords($user_id, $source, $receive_id, $records_ids, $receive_user_ids, $receive_group_ids);
        }

        if ($ids === false) {
            return $this->ajaxError('转发失败...');
        }

        //这里需要调用WebSocket推送接口
        $this->requestProxy->send('proxy/event/forward-chat-records', [
            'records_id' => $ids
        ]);

        return $this->ajaxSuccess('转发成功...');
    }

    /**
     * 获取转发记录详情
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForwardRecords()
    {
        $records_id = $this->request->post('records_id', 0);
        if (!isInt($records_id)) {
            return $this->ajaxParamError();
        }

        $rows = $this->chatLogic->getForwardRecords($this->uid(), $records_id);
        return $this->ajaxSuccess('success', ['rows' => $rows]);
    }

    /**
     * 上传聊天对话图片
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTaklImg()
    {
        $file = $this->request->file('img');
        if (!$file->isValid()) {
            return $this->ajaxParamError('请求参数错误');
        }

        $ext = $file->getClientOriginalExtension();
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->ajaxParamError('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        $imgInfo = getimagesize($file->getRealPath());
        $filename = getSaveImgName($ext, $imgInfo[0], $imgInfo[1]);

        //保存图片
        if (!$save_path = Storage::disk('uploads')->putFileAs('images/talks/' . date('Ymd'), $file, $filename)) {
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

        return $result ? $this->ajaxSuccess('图片上传成功...', ['file_info' => $result->id]) : $this->ajaxError('图片上传失败');
    }

    /**---------------------------*/

    /**
     * 查询聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function findChatRecords()
    {
        $user_id = $this->uid();
        $receive_id = $this->request->get('receive_id', 0);
        $source = $this->request->get('source', 0);
        $record_id = $this->request->get('record_id', 0);
        $limit = 30;

        if (!isInt($receive_id) || !isInt($source) || !isInt($record_id, true)) {
            return $this->ajaxParamError();
        }

        //判断是否属于群成员
        if ($source == 2 && !ChatService::checkGroupMember($receive_id, $user_id)) {
            $this->ajaxReturn(301, '非群聊成员不能查看群聊信息');
        }

        if ($result = $this->chatLogic->getChatsRecords($user_id, $receive_id, $source, $record_id, $limit, [1, 2, 4])) {
            //消息处理
            $result = array_map(function ($item) use ($user_id) {
                $item['float'] = $item['user_id'] == 0 ? 'center' : ($item['user_id'] == $user_id ? 'right' : 'left');
                $item['file_url'] = '';
                $item['friend_remarks'] = '';

                $item['forward_info'] = [];

                //消息类型处理
                switch ($item['msg_type']) {
                    case 1://文字消息
                        if ($item['is_code'] == 0) {
                            $item['content'] = replaceUrlToLink($item['content']);
                            $item['content'] = emojiReplace($item['content']);
                        } else {
                            $item['content'] = htmlspecialchars_decode($item['content']);
                        }

                        break;
                    case 2://文字消息
                        $item['file_url'] = ($item['msg_type'] == 2) ? getFileUrl($item['save_dir']) : '';
                        break;
                    case 4://会话记录转发消息
                        $item['forward_info'] = json_decode(UsersChatRecordsForward::where('id', $item['forward_id'])->value('text'), true);
                        break;
                }

                return $item;
            }, $result);
        }

        return $this->ajaxSuccess('success', [
            'rows' => $result,
            'record_id' => $result ? $result[count($result) - 1]['id'] : 0,
            'limit' => $limit
        ]);
    }

    /**
     * 搜索聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchChatRecords()
    {
        $receive_id = $this->request->get('receive_id', 0);
        $source = $this->request->get('source', 0);
        $keywords = $this->request->get('keywords', '');
        $date = $this->request->get('date', '');
        $page = $this->request->get('page', 1);
        $page_size = $this->request->get('page_size', 30);

        if (!isInt($receive_id) || !in_array($source, [1, 2]) || !isInt($page)) {
            return $this->ajaxParamError();
        }

        $params = [];
        if (!empty($keywords)) {
            $params['keywords'] = addslashes($keywords);
        }

        if (!empty($date)) {
            $params['date'] = $date;
        }

        $user_id = $this->uid();
        $result = $this->chatLogic->searchChatRecords($user_id, $receive_id, $source, $page, $page_size, $params);
        if ($result['rows']) {
            $result['rows'] = array_map(function ($items) use ($keywords) {
                $items['content'] = str_replace($keywords, "<mark>{$keywords}</mark>", $items['content']);
                return $items;
            }, $result['rows']);
        }

        return $this->ajaxSuccess('success', $result);
    }

    /**
     * 获取聊天记录上下文数据
     */
    public function getRecordsContext()
    {
        $user_id = $this->uid();
        $receive_id = $this->request->get('receive_id', 0);
        $source = $this->request->get('source', 0);
        $record_id = $this->request->post('record_id', 0);
        $find_mode = $this->request->post('find_mode', 1);
        $first_load = $this->request->post('first_load', 'true');

        if (!isInt($receive_id) || !in_array($source, [1, 2]) || !isInt($record_id, true) || !in_array($find_mode, [1, 2])) {
            return $this->ajaxParamError();
        }

        if ($first_load == 'true') {
            $rows = $this->chatLogic->getRecordsContexts($user_id, $receive_id, $source, 2, $record_id, 30, [1, 2, 4], true);
        } else {
            $rows = $this->chatLogic->getRecordsContexts($user_id, $receive_id, $source, $find_mode, $record_id, 30, [1, 2, 4]);
        }

        //消息处理
        $rows = array_map(function ($item) use ($user_id) {
            $item['file_url'] = '';
            $item['friend_remarks'] = '';

            $item['forward_info'] = [];

            //消息类型处理
            switch ($item['msg_type']) {
                case 1://文字消息
                    if ($item['is_code'] == 0) {
                        $item['content'] = replaceUrlToLink($item['content']);
                        $item['content'] = emojiReplace($item['content']);
                    } else {
                        $item['content'] = htmlspecialchars_decode($item['content']);
                    }

                    break;
                case 2://文字消息
                    $item['file_url'] = ($item['msg_type'] == 2) ? getFileUrl($item['save_dir']) : '';
                    break;
                case 4:
                    $item['forward_info'] = json_decode(UsersChatRecordsForward::where('id', $item['forward_id'])->value('text'), true);
                    break;
            }

            return $item;
        }, $rows);

        return $this->ajaxSuccess('success', [
            'rows' => $rows,
            'record_id' => $rows ? $rows[count($rows) - 1]['id'] : 0,
            'limit' => $rows
        ]);
    }
}
