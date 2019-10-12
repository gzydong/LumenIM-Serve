<?php
namespace App\Helpers;


class WebSocketHelper
{

    //通过用户ID可找到用户的fd
    const BIND_USER_TO_FD ='hash.users.fds';
    const BIND_FD_TO_USER ='hash.fds.list';

    /**
     * 获取reids 实例
     *
     * @return \Laravel\Lumen\Application|mixed
     */
    public function getRedis(){
        return app('redis.connection');
    }

    /**
     * 绑定用户ID和fd的关系(一个用户只能拥有一个fd)
     *
     * @param int $user_id
     * @param int $fd
     */
    public function bindUserFd(int $user_id,int $fd){
        $this->getRedis()->hset(self::BIND_FD_TO_USER,$user_id,$fd);
        $this->getRedis()->hset(self::BIND_USER_TO_FD,$fd,$user_id);
    }

    /**
     * 获取指定用户的fd
     *
     * @param int $user_id  用户ID
     * @return mixed
     */
    public function getUserFd(int $user_id){
        return $this->getRedis()->hget(self::BIND_FD_TO_USER,$user_id) | 0;
    }

    /**
     * 根据fd获取对应的用户ID
     * @param int $fd
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
     * @param int $fd
     */
    public function clearFdCache(int $fd){
        $user_id = $this->getRedis()->hget(self::BIND_USER_TO_FD,$fd);
        $this->getRedis()->hdel(self::BIND_FD_TO_USER,$user_id);
        $this->getRedis()->hdel(self::BIND_USER_TO_FD,$fd);
    }
}
