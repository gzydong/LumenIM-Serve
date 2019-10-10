<?php
namespace App\Services\Websocket;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Server\Facades\Server;

use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

class SocketHandler  extends WebsocketHandler
{

    public function onOpen($fd, Request $request)
    {
        echo date('Y-m-d H:i:s')." {$fd}连接了".PHP_EOL;


        return true;
    }

    public function onMessage(Frame $frame)
    {
        $wsServer = App::make(Server::class);

        foreach ($wsServer->connections as $fd) {
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($wsServer->isEstablished($fd)) {
                $wsServer->push($fd, $frame->data);
            }
        }

        return true;
    }

    public function onClose($fd, $reactorId)
    {
        echo date('Y-m-d H:i:s')." {$fd} 关闭了连接".PHP_EOL;
        return true;
    }

    //这里可定义其他事件名
}