<?php

namespace App\Cache;

/**
 * Class ApplyNumCache
 * @package App\Cache
 */
class ApplyNumCache
{

    const KEY = 'friend:apply:unread:num';

    /**
     * 获取好友未读申请数
     *
     * @param int $user_id 用户ID
     * @return string
     */
    public static function get(int $user_id)
    {
        return app('redis')->hget(self::KEY, $user_id);
    }

    /**
     * 设置未读好友申请数（自增加1）
     *
     * @param int $user_id 用户ID
     * @return int
     */
    public static function setInc(int $user_id)
    {
        return app('redis')->hincrby(self::KEY, $user_id, 1);
    }

    /**
     * 删除好友申请未读数
     *
     * @param int $user_id
     */
    public static function del(int $user_id)
    {
        app('redis')->hdel(self::KEY, $user_id);
    }
}
