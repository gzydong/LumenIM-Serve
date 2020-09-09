<?php

namespace App\Helpers\Cache;

use Illuminate\Support\Facades\Redis;

/**
 * 自定义的缓存助手
 * Class CacheHelper
 * @package App\Helpers\Cache
 */
class CacheHelper extends CacheFlag
{
    /**
     * 设置好友之间或群聊中发送的最后一条消息缓存
     *
     * @param array $message 消息内容
     * @param int $receive 接收者
     * @param int $sender 发送者(注：若聊天消息类型为群聊消息 $sender 应设置为0)
     */
    public static function setLastChatCache(array $message, int $receive, $sender = 0)
    {
        $key = $receive < $sender ? "{$receive}_{$sender}" : "{$sender}_{$receive}";
        Redis::hset(self::lastChatCacheKey($sender), $key, serialize($message));
    }

    /**
     * 获取好友之间或群聊中发送的最后一条消息缓存
     *
     * @param int $receive 接收者
     * @param int $sender 发送者(注：若聊天消息类型为群聊消息 $sender 应设置为0)
     * @return mixed
     */
    public static function getLastChatCache(int $receive, $sender = 0)
    {
        $key = $receive < $sender ? "{$receive}_{$sender}" : "{$sender}_{$receive}";
        $data = Redis::hget(self::lastChatCacheKey($sender), $key);

        return $data ? unserialize($data) : null;
    }

    /**
     * 设置添加好友申请未读消息数量
     *
     * @param int $user_id 用户ID
     * @param int $num 0是自增1, 1是清空
     */
    public static function setFriendApplyUnreadNum(int $user_id, $num = 0)
    {
        if ($num == 0) {
            Redis::hincrby(self::applyUnreadNumCacheKey(), $user_id, 1);
        } else {
            Redis::hdel(self::applyUnreadNumCacheKey(), $user_id);
        }
    }

    /**
     * 获取好友申请未读消息数量
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public static function getFriendApplyUnreadNum(int $user_id)
    {
        return Redis::hget(self::applyUnreadNumCacheKey(), $user_id);
    }

    /**
     * 设置好友备注缓存
     * @param int $user_id 用户ID
     * @param int $friend_id 朋友ID
     * @param string $friend_remark 朋友备注
     */
    public static function setFriendRemarkCache(int $user_id, int $friend_id, string $friend_remark)
    {
        $result = self::getFriendRemarkCache($user_id, $friend_id, 1);
        $key = $user_id > $friend_id ? "{$friend_id}_$user_id" : "{$user_id}_$friend_id";
        if (!$result) {
            $result = [];
        }

        $len = Redis::hlen(self::friendRemarkCacheKey());
        $result[$friend_id] = $friend_remark;

        Redis::hset(self::friendRemarkCacheKey(), $key, json_encode($result));
        if($len == 0){
            Redis::expire(self::friendRemarkCacheKey(),60*60*1);
        }
    }

    /**
     * 获取好友备注缓存
     *
     * @param int $user_id 用户ID
     * @param int $friend_id 朋友ID
     * @param int $isAll
     * @return mixed|null
     */
    public static function getFriendRemarkCache(int $user_id, int $friend_id, $isAll = 0)
    {
        $key = $user_id > $friend_id ? "{$friend_id}_$user_id" : "{$user_id}_$friend_id";
        $result = Redis::hget(self::friendRemarkCacheKey(), $key);

        if (!$result) return null;

        $result = json_decode($result, true);

        if ($isAll) {
            return $result;
        }

        if (array_has($result, $friend_id)) {
            return $result[$friend_id];
        }

        return null;
    }
}
