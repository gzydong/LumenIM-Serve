<?php

namespace App\Services\Socket;

use App\Helpers\JwtAuth;
use App\Models\UsersFriends;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use Illuminate\Http\Request;

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
        $token = $request->get('token','');
        $auth = new JwtAuth();
        $auth->setToken($token);
        $auth->decode();

        $user_id = $auth->getUid();

//        echo "用户ID： {$user_id} 已成功连接socket".PHP_EOL;

        $socket = app('SocketFdUtil');

        //获取用户所有客户端ID
        $fds = $socket->getUserFds($user_id);

        $socket->handle($fd,$user_id);

        //判断用户是否在其它地方登陆
        if(!$fds){
            //获取所有好友的用户ID
            if($uids = UsersFriends::getFriendIds($user_id)){
                $ffds = [];//所有好友的客户端ID

                foreach ($uids as $friends_id){
                    $ffds = array_merge($ffds,$socket->getUserFds($friends_id));
                }

                if($ffds){
                    $socket->sendResponseMessage('login_notify',$ffds,['user_id'=>$user_id,'status'=>1,'notify'=>'好友上线通知...']);
                }
            }
        }

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
        $socket = app('SocketFdUtil');

        //获取客户端对应的用户ID
        $user_id = $socket->getFdUserId($fd);

        //获取用户所有客户端ID
        $socket->clearBindFd($fd);

        echo "用户ID : {$user_id} - [{$fd}] 已成功退出socket连接".PHP_EOL;

        //多平台登录处理
        if(empty($socket->getUserFds($user_id))){
            //获取所有好友的用户ID
            if($uids = UsersFriends::getFriendIds($user_id)){
                $fds = [];

                foreach ($uids as $friends_id){
                    $fds = array_merge($fds,$socket->getUserFds($friends_id));
                }

                if($fds){
                    $socket->sendResponseMessage('login_notify',array_unique($fds),['user_id'=>$user_id,'status'=>0,'notify'=>'好友离线通知通知...']);
                }
            }
        }

        return true;
    }
}
