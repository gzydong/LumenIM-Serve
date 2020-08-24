<?php

namespace App\Http\Controllers\Socket;

use App\Facades\SocketResourceHandle;
use App\Helpers\Cache\CacheHelper;
use App\Helpers\Socket\NotifyInterface;
use App\Models\ChatRecords;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Http\Controllers\Controller;

class NotifyController extends Controller
{
    /**
     * 聊天对话消息
     */
    public function talk($websocket, $msgData)
    {
        $fd = $msgData['fd'];

        //获取客户端绑定的用户ID
        $uid = SocketResourceHandle::getFdUserId($fd);

        //检测发送者与客户端是否是同一个用户
        if ($uid != $msgData['send_user']) {
            SocketResourceHandle::response('notify', $fd, ['notify' => '非法操作!!!']);
            return true;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($msgData['source_type'], [1, 2])) {
            return true;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        if ($msgData['source_type'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!UsersFriends::isFriend($msgData['send_user'], $msgData['receive_user'])) {
                SocketResourceHandle::response('notify', $fd, ['notify' => '温馨提示:您当前与对方尚未成功好友！']);
                return true;
            }
        } else if ($msgData['source_type'] == 2) {//群聊
            //判断是否属于群成员
            if (!UsersGroup::isMember($msgData['receive_user'], $msgData['send_user'])) {
                SocketResourceHandle::response('notify', $fd, ['notify' => '温馨提示:您还没有加入该聊天群！']);
                return true;
            }
        }

        $result = ChatRecords::create([
            'source' => $msgData['source_type'],
            'msg_type' => 1,
            'user_id' => $msgData['send_user'],
            'receive_id' => $msgData['receive_user'],
            'content' => $msgData['text_message'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$result) {
            return false;
        }

        //获取消息推送的客户端
        $clientFds = [];
        if ($msgData['source_type'] == 1) {//私聊
            $msg_text = mb_substr($result->content,0,30);
            // 缓存最后一条消息
            CacheHelper::setLastChatCache([
                'text'=>$msg_text,
                'created_at'=>$result->created_at
            ],$msgData['receive_user'],$msgData['send_user']);

            CacheHelper::setChatUnreadNum($result->receive_id, $result->user_id);

            $clientFds = array_unique(array_merge(SocketResourceHandle::getUserFds($msgData['receive_user']), SocketResourceHandle::getUserFds($msgData['send_user'])));
        } else if ($msgData['source_type'] == 2) {
            $clientFds = SocketResourceHandle::getRoomGroupName($msgData['receive_user']);
        }

        SocketResourceHandle::response('chat_message', $clientFds, [
            'send_user' => $msgData['send_user'],
            'receive_user' => $msgData['receive_user'],
            'source_type' => $msgData['source_type'],
            'data' => NotifyInterface::formatTalkMsg([
                "id" => $result->id,
                "source" => $result->source,
                "msg_type" => 1,
                "user_id" => $result->user_id,
                "receive_id" => $result->receive_id,
                "content" => htmlspecialchars_decode($result->content),
                "created_at" => $result->created_at,
            ])
        ]);
    }

    /**
     * 键盘输入事件消息
     *
     * @param  $websocket
     * @param array $data 接收数据
     */
    public function keyboard($websocket, $data)
    {
        $clientFds = SocketResourceHandle::getUserFds($data['receive_user']);
        if ($clientFds) {
            SocketResourceHandle::response('input_tip', $clientFds, $data);
        }
    }
}
