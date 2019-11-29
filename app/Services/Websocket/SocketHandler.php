<?php

namespace App\Services\Websocket;

use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

use Illuminate\Http\Request;
use App\Helpers\RsaMeans;
use App\Facades\WebSocketHelper;
use App\Facades\ChatService;

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

        //获取客户端绑定的用户ID
        $uid = WebSocketHelper::getFdUserId($frame->fd);


        //检测发送者与客户端是否是同一个用户
        if ($uid != $msgData['sendUser']) {
            WebSocketHelper::sendResponseMessage('notify', $frame->fd, ['notify' => '非法操作!!!']);
            return true;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($msgData['sourceType'], [1, 2])){
            return true;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        if ($msgData['sourceType'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!ChatService::checkFriends($msgData['sendUser'], $msgData['receiveUser'])) {
                WebSocketHelper::sendResponseMessage('notify', $frame->fd, ['notify'=>'温馨提示:您当前与对方尚未成功好友！']);
                return true;
            }
        } else if ($msgData['sourceType'] == 2) {//群聊
            //判断是否属于群成员
            if (!ChatService::checkGroupMember($msgData['receiveUser'], $msgData['sendUser'])) {
                WebSocketHelper::sendResponseMessage('notify', $frame->fd, ['notify'=>'温馨提示:您还没有加入该聊天群！']);
                return true;
            }
        }

        //处理文本消息
        if ($msgData['msgType'] == 1) {
            $msgData["textMessage"] = htmlspecialchars($msgData['textMessage']);
        }

        //将聊天记录保存到数据库(待优化：后面采用异步保存信息)
        if (!$packageData = ChatService::saveChatRecord($msgData)) {
            info("聊天记录保存失败：" . json_encode($msgData));
        }

        //获取消息接收的客户端
        $clientFds = [];
        if ($msgData['sourceType'] == 1) {//私聊
            $clientFds = WebSocketHelper::getUserFds($msgData['receiveUser']);
        } else if ($msgData['sourceType'] == 2) {
            $clientFds = WebSocketHelper::getRoomGroupName($msgData['receiveUser']);
        }

        //用户信息
        $userInfo = [
            'user_id' => $msgData['sendUser'],//用户ID
            'avatar' => '',//用户头像
            'nickname' => '',//用户昵称
            'remark_nickname' => ''//好友备注或用户群名片
        ];

        //获取群聊用户信息
        if ($msgData['sourceType'] == 2) {
            if ($info = ChatService::getUsersGroupMemberInfo($msgData['receiveUser'], $msgData['sendUser'])) {
                $userInfo['avatar'] = $info['avatar'];
                $userInfo['nickname'] = $info['nickname'];
                $userInfo['remark_nickname'] = $info['visit_card'];
            }
        }

        //消息发送者用户信息
        $msgData['sendUserInfo'] = $userInfo;

        //替换表情
        if ($msgData['msgType'] == 1) {
            $msgData["textMessage"] = emojiReplace($msgData['textMessage']);
        }

        //发送消息
        WebSocketHelper::sendResponseMessage('chat_message', $clientFds, $msgData);
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
