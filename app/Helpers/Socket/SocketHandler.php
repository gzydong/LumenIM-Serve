<?php

namespace App\Helpers\Socket;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Models\UsersFriends;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use App\Facades\SocketResourceHandle;

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

        //获取用户所有客户端ID
        $fds = SocketResourceHandle::getUserFds($user_id);

        SocketResourceHandle::bindHandle($fd,$user_id);

        //判断用户是否在其它地方登陆
        if(!$fds){
            //获取所有好友的用户ID
            if($uids = UsersFriends::getFriendIds($user_id)){
                $ffds = [];//所有好友的客户端ID

                foreach ($uids as $friends_id){
                    $ffds = array_merge($ffds,SocketResourceHandle::getUserFds($friends_id));
                }

                if($ffds){
                    SocketResourceHandle::responseResource('login_notify',$ffds,['user_id'=>$user_id,'status'=>1,'notify'=>'好友上线通知...']);
                }
            }
        }

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
        //获取客户端对应的用户ID
        $user_id = SocketResourceHandle::getFdUserId($fd);

        //获取用户所有客户端ID
        SocketResourceHandle::clearBindFd($fd);

        //多平台登录处理
        if(empty(SocketResourceHandle::getUserFds($user_id))){
            //获取所有好友的用户ID
            if($uids = UsersFriends::getFriendIds($user_id)){
                $fds = [];

                foreach ($uids as $friends_id){
                    $fds = array_merge($fds,SocketResourceHandle::getUserFds($friends_id));
                }

                if($fds){
                    SocketResourceHandle::responseResource('login_notify',array_unique($fds),['user_id'=>$user_id,'status'=>0,'notify'=>'好友离线通知通知...']);
                }
            }
        }

        return true;
    }
}
