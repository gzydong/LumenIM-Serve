<?php

namespace App\Helpers\Socket;

use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Models\UsersFriends;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

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
        $token = $request->get('token', '');
        $auth = new JwtAuth();
        $auth->setToken($token);
        $auth->decode();

        $user_id = $auth->getUid();

        //判断用户是否在其它地方登录
        $isLogin = app('client.manage')->isOnline($user_id);

        // 绑定用户与fd该功能了
        app('client.manage')->bindFdToUser($fd, $user_id);
        app('client.manage')->bindUserIdToFds($fd, $user_id);

        // 绑定聊天群
        $group_ids = UsersLogic::getUserGroupIds($user_id);
        if ($group_ids) {
            foreach ($group_ids as $group_id) {
                app('room.manage')->bindUserToRoom($group_id, $user_id);
            }
        }

        //判断用户是否在其它地方登陆
        if (!$isLogin) {
            //获取所有好友的用户ID
            if ($uids = UsersFriends::getFriendIds($user_id)) {
                $ffds = [];//所有好友的客户端ID

                foreach ($uids as $friends_id) {
                    $ffds = array_merge($ffds, app('client.manage')->findUserIdFds($friends_id));
                }

                if ($ffds) {
                    SocketResourceHandle::response('login_notify', $ffds, ['user_id' => $user_id, 'status' => 1, 'notify' => '好友上线通知...']);
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
        $user_id = app('client.manage')->findFdUserId($fd);

        app('client.manage')->deleteFd($fd);

        // 判断用户是否多平台登录
        if (app('client.manage')->isOnline($user_id)) {
            return true;
        }

        //获取所有好友的用户ID
        $uids = UsersFriends::getFriendIds($user_id);
        if ($uids) {
            $fds = [];
            foreach ($uids as $friends_id) {
                $fds = array_merge($fds, app('client.manage')->findUserIdFds($friends_id));
            }

            if ($fds) {
                SocketResourceHandle::response('login_notify', array_unique($fds), [
                    'user_id' => $user_id,
                    'status' => 0,
                    'notify' => '好友离线通知通知...'
                ]);
            }
        }

        return true;
    }
}
