<?php

namespace App\Http\Controllers\Socket;

use App\Models\UserFriends;
use App\Models\Group\UserGroup;
use App\Models\Chat\ChatRecords;
use App\Http\Controllers\Controller;
use App\Cache\LastMsgCache;
use App\Helpers\PushMessageHelper;

class NotifyController extends Controller
{

    /**
     * 聊天对话消息
     *
     * @param $webSocket
     * @param array $msgData 接收数据
     * @return bool
     */
    public function talk($webSocket, $msgData)
    {
        $fd = $msgData['fd'];

        //获取客户端绑定的用户ID
        $uid = app('client.manage')->findFdUserId($fd);

        //检测发送者与客户端是否是同一个用户
        if ($uid != $msgData['send_user']) {
            PushMessageHelper::response('notify', $fd, ['notify' => '非法操作!!!']);
            return true;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($msgData['source_type'], [1, 2])) {
            return true;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        if ($msgData['source_type'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!UserFriends::isFriend($msgData['send_user'], $msgData['receive_user'])) {
                PushMessageHelper::response('notify', $fd, ['notify' => '温馨提示:您当前与对方尚未成功好友！']);
                return true;
            }
        } else if ($msgData['source_type'] == 2) {//群聊
            //判断是否属于群成员
            if (!UserGroup::isMember($msgData['receive_user'], $msgData['send_user'])) {
                PushMessageHelper::response('notify', $fd, ['notify' => '温馨提示:您还没有加入该聊天群！']);
                return true;
            }
        }

        $result = ChatRecords::create([
            'source' => $msgData['source_type'],
            'msg_type' => 1,
            'user_id' => $msgData['send_user'],
            'receive_id' => $msgData['receive_user'],
            'content' => htmlspecialchars($msgData['text_message']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$result) {
            return false;
        }

        //获取消息推送的客户端
        $clientFds = [];
        if ($msgData['source_type'] == 1) {//私聊
            $msg_text = mb_substr($result->content, 0, 30);
            // 缓存最后一条消息
            LastMsgCache::set([
                'text' => $msg_text,
                'created_at' => $result->created_at
            ], $msgData['receive_user'], $msgData['send_user']);

            $clientFds = array_unique(array_merge(
                app('client.manage')->findUserIdFds($msgData['receive_user']),
                app('client.manage')->findUserIdFds($msgData['send_user'])
            ));

            // 设置好友消息未读数
            app('unread.talk')->setInc($result->receive_id, $result->user_id);
        } else if ($msgData['source_type'] == 2) {
            $clientFds = app('room.manage')->getRoomGroupName($msgData['receive_user']);
        }

        if ($result->content) {
            $result->content = replace_url_link($result->content);
        }

        PushMessageHelper::response('chat_message', $clientFds, [
            'send_user' => $msgData['send_user'],
            'receive_user' => $msgData['receive_user'],
            'source_type' => $msgData['source_type'],
            'data' => PushMessageHelper::formatTalkMsg([
                "id" => $result->id,
                "source" => $result->source,
                "msg_type" => 1,
                "user_id" => $result->user_id,
                "receive_id" => $result->receive_id,
                "content" => $result->content,
                "created_at" => $result->created_at,
            ])
        ]);
    }

    /**
     * 键盘输入事件消息
     *
     * @param  $webSocket
     * @param array $data 接收数据
     */
    public function keyboard($webSocket, $data)
    {
        $clientFds = app('client.manage')->findUserIdFds($data['receive_user']);
        if ($clientFds) {
            PushMessageHelper::response('input_tip', $clientFds, $data);
        }
    }
}
