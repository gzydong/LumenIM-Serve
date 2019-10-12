<?php
namespace App\Services\Websocket;

use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Websocket\Facades\Room;


class TestHandler
{


    public function sendTest{
        //仅发送到发件人客户端
        Websocket::emit('message', 'this is a test');

        //发送到除发件人以外的所有客户端
        Websocket::broadcast()->emit('message', 'this is a test');

        //发送到“游戏”室中的所有客户端（发件人除外）
        Websocket::broadcast()->to('game')->emit('message', 'nice game');

        //发送到“game1”和“game2”房间中的所有客户端，发件人除外
        Websocket::broadcast()->to('game1')->to('game2')->emit('message', 'nice game');
        Websocket::broadcast()->to(['game1', 'game2'])->emit('message', 'nice game');

        //发送到“游戏”中的所有客户端，包括发送客户端
        Websocket::to('game')->emit('message', 'enjoy the game');

        //发送到单个socketid 1（不能是发件人）
        Websocket::broadcast()->to(1)->emit('message', 'for your eyes only');

        //发送到socketid 1和2（不能是发件人）
        Websocket::broadcast()->to(1)->to(2)->emit('message', 'for your eyes only');
        Websocket::broadcast()->to([1, 2])->emit('message', 'for your eyes only');

        //连接以订阅给定通道的套接字
        Websocket::join('some room');

        //保留以取消订阅给定频道的套接字
        Websocket::leave('some room');
    }

    public function roomTest(){
        //把所有的FD都安排在游戏室里
        Room::getClients('game');

        //把1号FD的所有房间
        Room::getRooms(1);

        //将FD 1添加到“游戏”室
        Room::add(1, 'room');

        //将FD 1添加到“游戏”和“测试”室
        Room::add(1, ['game', 'test']);

        //从“游戏”室删除FD 1
        Room::delete(1, 'room');

        //从“游戏”和“测试”室删除FD 1
        Room::delete(1, ['game', 'test']);
    }
}
