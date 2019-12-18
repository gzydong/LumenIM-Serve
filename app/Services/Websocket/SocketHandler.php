<?php

namespace App\Services\Websocket;

use App\Models\UsersFriends;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use Illuminate\Http\Request;
use App\Helpers\RsaMeans;
use App\Facades\WebSocketHelper;

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

        //获取用户所有客户端ID
        $fds = WebSocketHelper::getUserFds($user_id);

        //这里处理用户登录后的逻辑
        WebSocketHelper::bindUserFd($user_id, $fd);   //绑定用户ID与fd的关系
        WebSocketHelper::bindGroupChats($user_id, $fd);//绑定群聊关系

        //判断用户是否在其它地方登陆
        if(!$fds){
            //获取所有好友的用户ID
            if($uids = UsersFriends::getFriendIds($user_id)){
                $ffds = [];//所有好友的客户端ID

                echo '朋友ID:'.PHP_EOL;
                var_dump($uids);
                foreach ($uids as $friends_id){
                    $ffds = array_merge($ffds,WebSocketHelper::getUserFds($friends_id));
                }

                var_dump($ffds);

                if($ffds){
                    WebSocketHelper::sendResponseMessage('login_notify',$ffds,['user_id'=>$user_id,'notify'=>'好友上线通知...']);
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
        WebSocketHelper::clearFdCache($fd);

        return true;
    }
}
