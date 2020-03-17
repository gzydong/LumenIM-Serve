<?php

namespace App\Helpers;

use App\Logic\UsersLogic;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;

/**
 * SocketFd fd 处理类
 *
 * 注释:一个fd客户单ID对应一个用户，一个用户对应多个客户端fd
 */
class SocketFdUtil
{

    //fd与用户绑定(使用hash 做处理)
    const BIND_FD_TO_USER = 'socket:fd:user';

    //使用集合做处理
    const BIND_USER_TO_FDS = 'socket:user:fds:';

    const ROOM_GROUP_PREFIX = 'socket:group:chat';

    //消息事件类型
    const EVENTS = [
        //别名 =》 事件名
        'chat_message'=>'chat_message',//用户聊天消息
        'friend_apply'=>'friend_apply',//好友添加申请消息
        'join_group'=>'join_group',    //入群消息
        'login_notify'=>'login_notify',//好友登录消息通知
        'input_tip'=>'input_tip',//好友登录消息通知
    ];

    /**
     * 获取reids 实例
     *
     * @return mixed
     */
    private function getRedis()
    {
        return app('redis.connection');
    }

    /**
     * 获取用户的绑定的fds的集合名
     *
     * @param int $user_id 用户ID
     * @return string
     */
    private function getUserFdsName(int $user_id)
    {
        return self::BIND_USER_TO_FDS . $user_id;
    }

    /**
     * 获取群聊ID的房间名称
     *
     * @param $group_id 群聊ID
     * @return string
     */
    public function getRoomGroupName($group_id)
    {
        return self::ROOM_GROUP_PREFIX . $group_id;
    }

    /**
     * fd 与用户ID绑定关系
     *
     * @param int $fd socket客户端ID
     * @param int $user_id 客户端连接的用户ID
     */
    public function handle(int $fd, int $user_id)
    {
        $this->bindFdUser($fd, $user_id);
        $this->bindUserFds($fd, $user_id);
        $this->bindGroupChats($fd, $user_id);
    }

    /**
     * 绑定fd与用户ID一一对应关系
     *
     * @param int $fd socket客户端ID
     * @param int $user_id 客户端连接的用户ID
     * @return mixed
     */
    private function bindFdUser(int $fd, int $user_id)
    {
        return $this->getRedis()->hset(self::BIND_FD_TO_USER, $fd, $user_id);
    }

    /**
     * 将fd绑定到用户的连接的fd集合中
     *
     * @param int $fd socket客户端ID
     * @param int $user_id 客户端连接的用户ID
     * @return mixed
     */
    private function bindUserFds(int $fd, int $user_id)
    {
        return $this->getRedis()->sadd($this->getUserFdsName($user_id), $fd);
    }

    /**
     * 绑定用户所有的群聊到聊天室
     *
     * @param int $fd socket客户端ID
     * @param int $user_id 用户ID
     * @return bool
     */
    public function bindGroupChats(int $fd, int $user_id)
    {
        $ids = UsersLogic::getUserGroupIds($user_id);
        if (empty($ids)) {
            return true;
        }

        //将用户添加到所在的所有房间里
        $rooms = array_map(function ($group_id) {
            return self::ROOM_GROUP_PREFIX . $group_id;
        }, $ids);

        Room::add($fd, $rooms);
    }

    /**
     * 绑定指定的用户到指定的聊天室
     *
     * @param int $group_id 群聊ID
     * @param int $user_id 用户ID
     */
    public function bindUserGroupChat(int $group_id, int $user_id)
    {
        $room = $this->getRoomGroupName($group_id);
        foreach ($this->getUserFds($user_id) as $fd) {
            Room::add($fd, $room);
        }
    }

    /**
     * 清除fd绑定的相关信息
     *
     * @param int $fd
     */
    public function clearBindFd(int $fd)
    {
        $user_id = $this->getFdUserId($fd) | 0;

        $this->getRedis()->hdel(self::BIND_FD_TO_USER, $fd);
        $this->getRedis()->srem($this->getUserFdsName($user_id), $fd);

        $this->clearGroupRooms($fd);
    }

    /**
     * 退出指定的聊天室
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群聊ID
     * @return bool
     */
    public function clearGroupRoom(int $user_id, int $group_id)
    {
        $room = $this->getRoomGroupName($group_id);
        $fds = $this->getUserFds($user_id);

        if (!$fds) return false;

        foreach ($fds as $fd) {
            Room::delete($fd, $room);
        }

        return true;
    }

    /**
     * 清除fd 所在的所有聊天室
     *
     * @param int $fd socket客户端ID
     */
    public function clearGroupRooms(int $fd)
    {
        if ($rooms = Room::getRooms($fd)) {
            Room::delete($fd, $rooms);
        }
    }

    /**
     * 清除redis 缓存信息
     */
    public function clearRedisCache(){
        $keys = $this->getRedis()->keys(self::BIND_USER_TO_FDS . "*");
        foreach ($keys as $keyName){
            $this->getRedis()->del($keyName);
        }

        $this->getRedis()->del(self::BIND_FD_TO_USER);
    }

    /**
     * 获取客户端对应的用户ID
     *
     * @param int $fd socket客户端ID
     * @return mixed
     */
    public function getFdUserId(int $fd)
    {
        return $this->getRedis()->hget(self::BIND_FD_TO_USER, $fd) | 0;
    }

    /**
     * 获取用户所有的客户端ID
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserFds(int $user_id)
    {
        return $this->getRedis()->smembers($this->getUserFdsName($user_id));
    }

    /**
     * 发送Websocket 响应信息
     *
     * @param string $event 消息事件名
     * @param int|array $receive 接受者
     * @param string|array $data
     */
    public function sendResponseMessage(string $event,$receive,$data){
        if(isset(self::EVENTS[$event])){
            if(in_array($event,['login_notify','input_tip'])){
                Websocket::broadcast()->to($receive)->emit(self::EVENTS[$event], $data);
            }else if(!empty($receive)){
                Websocket::to($receive)->emit(self::EVENTS[$event], $data);
            }else{
                Websocket::emit(self::EVENTS[$event], $data);
            }
        }

        unset($receive);unset($data);
    }
}
