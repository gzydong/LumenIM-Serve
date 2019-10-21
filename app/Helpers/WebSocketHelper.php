<?php
namespace App\Helpers;

use App\Logic\UsersLogic;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Server\Facades\Server;


/**
 * WebSocket 处理类
 * @package App\Helpers
 */
class WebSocketHelper
{

    //通过用户ID可找到用户的fd
    const BIND_USER_TO_FD ='hash.users.fds';
    const BIND_FD_TO_USER ='hash.fds.list';
    const ROOM_GROUP_PREFIX ='room.group.chat';


    //消息事件类型
    const EVENTS = [
        'chat_message'=>'chat_message',
        'friend_apply'=>'friend_apply',
    ];

    /**
     * 获取reids 实例
     *
     * @return \Laravel\Lumen\Application|mixed
     */
    public function getRedis(){
        return app('redis.connection');
    }

    /**
     * 绑定用户ID和fd的关系(注：用户与Fd 的关系是一对多 、Fd与用户 的关系是一对一)
     *
     * @param int $user_id 用户ID
     * @param int $fd      Websocket 连接标识[fd]
     */
    public function bindUserFd(int $user_id,int $fd){
        $fds = $this->getUserFds($user_id);
        $fds[] = $fd;
        $this->getRedis()->hset(self::BIND_FD_TO_USER,$user_id,implode(',',$fds));
        $this->getRedis()->hset(self::BIND_USER_TO_FD,$fd,$user_id);
        unset($fds);
    }

    /**
     * 获取指定用户的fd(一个用户可能存在多个fd)
     *
     * @param int $user_id  用户ID
     * @return mixed
     */
    public function getUserFds(int $user_id){
        $fds = $this->getRedis()->hget(self::BIND_FD_TO_USER,$user_id);
        return $fds ? explode(',',$fds) : [];
    }

    /**
     * 根据fd获取对应的用户ID
     *
     * @param int $fd Websocket 连接标识[fd]
     * @return int
     */
    public function getFdUserId(int $fd){
        return $this->getRedis()->hget(self::BIND_USER_TO_FD,$fd) | 0;
    }

    /**
     * 清除redis 缓存信息
     */
    public function clearRedisCache(){
        $this->getRedis()->del(self::BIND_USER_TO_FD);
        $this->getRedis()->del(self::BIND_FD_TO_USER);
    }

    /**
     * 清除指定的fd缓存信息
     *
     * @param int $fd Websocket 连接标识[fd]
     */
    public function clearFdCache(int $fd){
        $user_id = $this->getFdUserId($fd);
        $fds = $this->getUserFds($user_id);
        if(count($fds) > 1){
            $this->getRedis()->hset(self::BIND_FD_TO_USER,$user_id,implode(',',array_diff($fds,[$fd])));
        }else{
            $this->getRedis()->hdel(self::BIND_FD_TO_USER,$user_id);
        }

        $this->getRedis()->hdel(self::BIND_USER_TO_FD,$fd);

        //清除fd 所在的所有聊天室
        $rooms = Room::getRooms($fd);
        Room::delete($fd, $rooms);unset($rooms);
    }

    /**
     * 绑定用户群聊关系
     *
     * @param int $user_id 用户ID
     * @param int $fd Websocket 连接标识[fd]
     * @return bool
     */
    public function bindGroupChat(int $user_id,int $fd){
        $ids = UsersLogic::getUserGroupIds($user_id);
        if(empty($ids)){
            return true;
        }

        //将用户添加到所在的所有房间里
        $rooms = array_map(function ($group_id){
            return self::ROOM_GROUP_PREFIX.$group_id;
        },$ids);

        Room::add($fd, $rooms);unset($rooms);
    }

    /**
     * 获取群聊ID的房间名称
     *
     * @param $group_id 群聊ID
     * @return string
     */
    public function getRoomGroupName($group_id){
        return self::ROOM_GROUP_PREFIX.$group_id;
    }

    /**
     * 统一发送websocket 响应信息
     *
     * @param $event
     * @param int|array $receive
     * @param string|array $data
     */
    public function sendResponseMessage(string $event,$receive,$data){
        if(isset(self::EVENTS[$event])){
            Websocket::to($receive)->emit(self::EVENTS[$event], $data);
        }

        unset($receive);unset($data);
    }

    /**
     * 服务端强制断开 Websocket 连接
     *
     * @param array $fds
     */
    public function disconnect(array $fds){
        $wsServer = app(Server::class);
        foreach ($fds as $fd){
            if($wsServer->exist($fd)){
                $wsServer->disconnect($fd,4030, "您的账号在其他设备登录，如果这不是您的操作，请及时修改您的登录密码");
            }
        }
    }
}
