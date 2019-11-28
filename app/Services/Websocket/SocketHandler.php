<?php

namespace App\Services\Websocket;

use App\Models\UsersGroupMember;
use Swoole\Websocket\Frame;
use Illuminate\Http\Request;
use App\Facades\WebSocketHelper;
use App\Facades\ChatService;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use App\Helpers\RsaMeans;

class SocketHandler extends WebsocketHandler
{

    /**
     * 连接成功方法
     *
     * @param int $fd
     * @param Request $request
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        $user_id = RsaMeans::decrypt($request->get('sid'));

        //这里处理用户登录后的逻辑
        WebSocketHelper::bindUserFd($user_id, $fd);   //绑定用户ID与fd的关系
        WebSocketHelper::bindGroupChats($user_id, $fd);//绑定群聊关系

        return true;
    }

    /**
     * 消息接收方法
     *
     * @param Frame $frame
     * @return bool|void
     */
    public function onMessage(Frame $frame)
    {
        $msgData = json_decode($frame->data, true);
        $msgData['send_time'] = date('Y-m-d H:i:s');

        //验证消息类型 私聊|群聊
        if (!in_array($msgData['sourceType'], [1, 2])) return true;

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        if (!ChatService::check($msgData)) return true;

        //处理文本消息
        if ($msgData['msgType'] == 1) {
            $msgData["textMessage"] = htmlspecialchars($msgData['textMessage']);
        }

        //将聊天记录保存到数据库(待优化：后面采用异步保存信息)
        if (!$packageData = ChatService::saveChatRecord($msgData)) {
            info("聊天记录保存失败：" . json_encode($msgData));
        }

        //获取消息接收的客户端
        $receive = [];
        if ($msgData['sourceType'] == 1) {//私聊
            $receive = WebSocketHelper::getUserFds($msgData['receiveUser']);
        } else if ($msgData['sourceType'] == 2) {
            $receive = WebSocketHelper::getRoomGroupName($msgData['receiveUser']);
        }

        //这里获取缓存信息
        $userInfo = [
            'user_id' => $msgData['sendUser'],//用户ID
            'avatar' => '',//用户头像
            'nickname' => '',//用户昵称
            'remark_nickname' => ''//好友备注或用户群名片
        ];

        //群聊消息
        if ($msgData['sourceType'] == 2) {
            $res = UsersGroupMember::from('users_group_member as ugm')
                ->select(['users.nickname', 'users.avatarurl', 'ugm.visit_card'])
                ->leftJoin('users', 'users.id', '=', 'ugm.user_id')
                ->where('ugm.group_id', $msgData['receiveUser'])->where('ugm.user_id', $msgData['sendUser'])
                ->first();

            $userInfo['avatar'] = $res->avatarurl;
            $userInfo['nickname'] = $res->nickname;
            $userInfo['remark_nickname'] = $res->visit_card;
        } else {//好友私聊消息

        }

        //消息发送者用户信息
        $msgData['sendUserInfo'] = $userInfo;

        //替换表情
        if ($msgData['msgType'] == 1) {
            $msgData["textMessage"] = emojiReplace($msgData['textMessage']);
        }

        //发送消息
        WebSocketHelper::sendResponseMessage('chat_message', $receive, $msgData);
        return true;
    }

    /**
     * 这里需要将fd关闭后的相关数据清除掉
     *
     * @param int $fd
     * @param int $reactorId
     * @return bool|void
     */
    public function onClose($fd, $reactorId)
    {
        WebSocketHelper::clearFdCache($fd);

        return true;
    }
}
