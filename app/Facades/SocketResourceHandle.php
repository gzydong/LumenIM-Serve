<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Helpers\Socket\SocketResourceHandle as Handle;

/**
 *
 * @method static $this response(string $event, $receive, $data) 推送 Socket 信息
 * @method static $this bindHandle(int $fd, int $user_id) Socket fd 与用户ID绑定关系
 * @method static $this bindUserGroupChat(int $group_id, int $user_id) 绑定指定的用户到指定的聊天室
 * @method static $this clearBindFd(int $fd) 清除fd绑定的相关信息
 * @method static $this clearGroupRoom(int $user_id, int $group_id) 退出指定的聊天室
 * @method static $this clearRedisCache() 清除 Redis 中 Socket fd 相关的缓存信息
 * @method static $this getFdUserId() 获取 Socket fd 对应的用户ID
 * @method static $this getUserFds() 获取用户所有的Socket fd
 * @method static $this getRoomGroupName() 获取群ID的房间名称
 *
 * Class SocketResourceHandle
 * @package App\Facades
 */
class SocketResourceHandle extends Facade
{
    /**
     * 获取组件的注册名称。
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Handle::class;
    }
}
