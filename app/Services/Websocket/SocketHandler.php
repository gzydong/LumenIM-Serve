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
