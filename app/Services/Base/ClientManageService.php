<?php

namespace App\Services\Base;

/**
 * 客户端Fd管理服务
 *
 * 注 :一个fd客户单ID对应一个用户，一个用户对应多个客户端fd
 *
 * Class ClientBindService
 * @package App\Services
 */
class ClientManageService
{

    //fd与用户绑定(使用hash 做处理)
    const BIND_FD_TO_USER = 'socket:fd:user';

    //使用集合做处理
    const BIND_USER_TO_FDS = 'socket:user:fds:';

    /**
     * 检测用户当前是否在线
     *
     * @param int $user_id 用户ID
     * @return bool
     */
    public function isOnline(int $user_id)
    {
        return $this->getRedis()->scard($this->getUserFdsName($user_id)) ? true : false;
    }

    /**
     * 查询客户端fd对应的用户ID
     * @param int $fd
     * @return int
     */
    public function findFdUserId(int $fd)
    {
        return $this->getRedis()->hget(self::BIND_FD_TO_USER, $fd) ?: 0;
    }

    /**
     * 查询用户的客户端fd集合(用户可能存在多端登录)
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function findUserIdFds(int $user_id)
    {
        return $this->getRedis()->smembers($this->getUserFdsName($user_id));
    }

    /**
     * 客户端fd绑定用户ID
     *
     * @param int $fd 客户端fd
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function bindFdToUser(int $fd, int $user_id)
    {
        return $this->getRedis()->hset(self::BIND_FD_TO_USER, $fd, $user_id);
    }

    /**
     * 将fd绑定到用户的连接的fd集合中
     *
     * @param int $fd 客户端fd
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function bindUserIdToFds(int $fd, $user_id)
    {
        return $this->getRedis()->sadd($this->getUserFdsName($user_id), $fd);
    }

    /**
     * 清除指定的客户端fd缓存信息
     *
     * @param int $fd 客户端fd
     */
    public function deleteFd(int $fd)
    {
        $user_id = $this->findFdUserId($fd) | 0;

        $this->getRedis()->hdel(self::BIND_FD_TO_USER, $fd);
        $this->getRedis()->srem($this->getUserFdsName($user_id), $fd);

        // 将fd 退出所有聊天室
        app('room.manage')->removeFdRoomAll($fd);
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
     * 清除 Redis 中 Socket fd 相关的缓存信息
     */
    public function clearRedisCache()
    {
        // 定义初始游标
        $cursor = 0;
        do {
            // 使用游标扫描匹配查询
            $result = $this->getRedis()->scan($cursor, 'MATCH', self::BIND_USER_TO_FDS . "*", 'count', 1000);

            // 游标赋值
            $cursor = intval($result[0]);
            if ($result[1]) {
                $this->getRedis()->del(...$result[1]);
            }
        } while ($cursor > 0);

        $this->getRedis()->del(self::BIND_FD_TO_USER);
    }

    /**
     * 获取Redis 实例
     *
     * @return mixed
     */
    private function getRedis()
    {
        return app('redis');
    }
}
