<?php

namespace App\Helpers\Socket;

use SwooleTW\Http\Websocket\Facades\Websocket;

/**
 * Socket 资源处理
 * Class SocketResourceHandle
 * @package App\Helpers
 */
class SocketResourceHandle extends SocketFdManage
{
    // 消息事件类型
    const events = [
        'chat_message',//用户聊天消息
        'friend_apply',//好友添加申请消息
        'join_group',    //入群消息
        'login_notify',//好友登录消息通知
        'input_tip',//好友登录消息通知
        'revoke_records',//好友撤回消息通知
    ];

    /**
     * 推送 Socket 信息
     * @param string $event 消息事件类型
     * @param $receive 接受者
     * @param array $data 数据包
     * @return bool
     */
    public function response(string $event, $receive, $data)
    {
        // 判断事件类型是否存在
        if (!in_array($event, self::events)) {
            return false;
        }

        if (in_array($event, ['login_notify', 'input_tip'])) {
            Websocket::broadcast()->to($receive)->emit($event, $data);
        } else if (!empty($receive)) {
            Websocket::to($receive)->emit($event, $data);
        } else {
            Websocket::emit($event, $data);
        }
    }
}
