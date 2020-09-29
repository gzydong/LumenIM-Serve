<?php

namespace App\WebSocket;

use App\Helpers\PushMessageHelper;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\UserFriends;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

class SocketHandler extends WebsocketHandler
{
    /**
     * 连接成功方法
     *
     * @param int $fd 客户端ID
     * @param Request $request
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        $token = $request->get('token', '');

        $user_id = 0;
        try {
            $jwtObject = app('jwt.auth')->decode($token);
            if ($jwtObject->getStatus() == 1) {
                $user_id = $jwtObject->getData()['uid'] ?? 0;
            }
        } catch (\Exception $e) {
        }

        //判断用户是否在其它地方登录
        $isLogin = app('client.manage')->isOnline($user_id);

        // 绑定用户与fd该功能了
        app('client.manage')->bindFdToUser($fd, $user_id);
        app('client.manage')->bindUserIdToFds($fd, $user_id);

        // 绑定聊天群
        $group_ids = UserService::getUserGroupIds($user_id);
        if ($group_ids) {
            foreach ($group_ids as $group_id) {
                app('room.manage')->bindUserToRoom($group_id, $user_id);
            }
        }

        //判断用户是否在其它地方登陆
        if (!$isLogin) {
            //获取所有好友的用户ID
            if ($uids = UserFriends::getFriendIds($user_id)) {
                $ffds = [];//所有好友的客户端ID

                foreach ($uids as $friends_id) {
                    $ffds = array_merge($ffds, app('client.manage')->findUserIdFds($friends_id));
                }

                if ($ffds) {
                    PushMessageHelper::response('login_notify', $ffds, ['user_id' => $user_id, 'status' => 1, 'notify' => '好友上线通知...']);
                }
            }
        }

        return true;
    }

    /**
     * Websocket 客户端断开事件
     *
     * @param int $fd 客户端ID
     * @param int $reactorId swoole 线程ID
     * @return bool|void
     */
    public function onClose($fd, $reactorId)
    {
        //获取客户端对应的用户ID
        $user_id = app('client.manage')->findFdUserId($fd);

        app('client.manage')->deleteFd($fd);

        // 将fd 退出所有聊天室
        app('room.manage')->removeFdRoomAll($fd);

        // 判断用户是否多平台登录
        if (app('client.manage')->isOnline($user_id)) {
            return true;
        }

        //获取所有好友的用户ID
        $uids = UserFriends::getFriendIds($user_id);
        if ($uids) {
            $fds = [];
            foreach ($uids as $friends_id) {
                $fds = array_merge($fds, app('client.manage')->findUserIdFds($friends_id));
            }

            if ($fds) {
                PushMessageHelper::response('login_notify', array_unique($fds), [
                    'user_id' => $user_id,
                    'status' => 0,
                    'notify' => '好友离线通知通知...'
                ]);
            }
        }

        return true;
    }
}
