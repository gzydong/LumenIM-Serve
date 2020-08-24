<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Logic\{ChatLogic, TalkLogic};

use App\Models\{ChatRecordsFile,
    UsersChatList,
    UsersFriends,
    UsersGroup
};

use App\Helpers\Cache\CacheHelper;
use App\Helpers\RequestProxy;

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
    public $talkLogic;
    public $requestProxy;

    public function __construct(Request $request, ChatLogic $chatLogic, RequestProxy $requestProxy, TalkLogic $talkLogic)
    {
        $this->request = $request;

        $this->chatLogic = $chatLogic;

        $this->requestProxy = $requestProxy;
        $this->talkLogic = $talkLogic;
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
            if (!UsersFriends::isFriend($uid, $receive_id)) {
                return $this->ajaxReturn(305, '暂不属于好友关系，无法进行聊天...');
            }
        } else {
            if (!UsersGroup::isMember($receive_id, $uid)) {
                return $this->ajaxReturn(305, '暂不属于群成员，无法进行群聊 ...');
            }
        }

        $id = UsersChatList::addItem($uid, $receive_id, $type);
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

        $isTrue = UsersChatList::delItem($this->uid(), $list_id);
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

        $isTrue = UsersChatList::topItem($this->uid(), $list_id, $type == 1);
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

        $isTrue = UsersChatList::notDisturbItem($this->uid(), $receive_id, $type, $not_disturb);

        return $isTrue ? $this->ajaxSuccess('设置成功...') : $this->ajaxError('设置失败...');
    }

    /**
     * 获取用户对话列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $rows = $this->talkLogic->talkLists($this->uid());
        if ($rows) {
            $rows = arraysSort($rows, 'updated_at');
        }

        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 获取指定的对话栏目
     */
    public function listItem()
    {

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
        if ($source == 2 && UsersGroup::isMember($receive_id, $user_id) == false) {
            return $this->ajaxSuccess('非群聊成员不能查看群聊信息', [
                'rows' => [],
                'record_id' => 0,
                'limit' => $limit
            ]);
        }

        $result = $this->talkLogic->getChatRecords($user_id, $receive_id, $source, $record_id, $limit);

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

        [$isTrue, $message, $data] = $this->talkLogic->revokeRecord($user_id, $record_id);
        if ($isTrue) {
            //这里需要调用WebSocket推送接口
            $this->requestProxy->send('proxy/event/revoke-records', [
                'record_id' => $data['id']
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

        $isTrue = $this->talkLogic->removeRecords($user_id, $source, $receive_id, $record_ids);
        return $isTrue ? $this->ajaxSuccess('删除成功...') : $this->ajaxError('删除失败...');
    }

    /**
     * 转发聊天记录(待优化)
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
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

        $items = array_merge(
            array_map(function ($friend_id) {
                return ['source' => 1, 'id' => $friend_id];
            }, $receive_user_ids),
            array_map(function ($group_id) {
                return ['source' => 2, 'id' => $group_id];
            }, $receive_group_ids)
        );

        if ($forward_mode == 1) {//单条转发
            $ids = $this->talkLogic->forwardRecords($user_id, $receive_id, $records_ids, $items);
        } else {//合并转发
            $ids = $this->talkLogic->mergeForwardRecords($user_id, $receive_id, $source, $records_ids, $items);
        }

        if (!$ids) {
            return $this->ajaxError('转发失败...');
        }

        if ($receive_user_ids) {
            foreach ($receive_user_ids as $v) {
                CacheHelper::setChatUnreadNum($v, $user_id);
            }
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

        $rows = $this->talkLogic->getForwardRecords($this->uid(), $records_id);
        return $this->ajaxSuccess('success', ['rows' => $rows]);
    }

    /**
     * 上传聊天对话图片（待优化）
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
        if (!$save_path = Storage::disk('uploads')->putFileAs('media/images/talks/' . date('Ymd'), $file, $filename)) {
            return $this->ajaxError('图片上传失败');
        }

        $result = ChatRecordsFile::create([
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
        if ($source == 2 && UsersGroup::isMember($receive_id, $user_id) == false) {
            return $this->ajaxSuccess('非群聊成员不能查看群聊信息', [
                'rows' => [],
                'record_id' => 0,
                'limit' => $limit
            ]);
        }

        $result = $this->talkLogic->getChatRecords($user_id, $receive_id, $source, $record_id, $limit, [1, 2, 5]);
        return $this->ajaxSuccess('success', [
            'rows' => $result,
            'record_id' => $result ? $result[count($result) - 1]['id'] : 0,
            'limit' => $limit
        ]);
    }

    /**
     * 搜索聊天记录（待优化）
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
                $items['content'] = str_ireplace($keywords, "<mark>{$keywords}</mark>", $items['content']);
                return $items;
            }, $result['rows']);
        }

        return $this->ajaxSuccess('success', $result);
    }

    /**
     * 获取聊天记录上下文数据（待优化）
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
