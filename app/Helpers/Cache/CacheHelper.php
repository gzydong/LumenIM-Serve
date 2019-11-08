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
     * @param string $message 消息内容
     * @param int $receive 接收者
     * @param int $sender  发送者(注：若聊天消息类型为群聊消息 $sender 应设置为0)
     */
    public static function setLastChatCache(string $message,int $receive,$sender = 0){
        $key = $receive < $sender ? "{$receive}_{$sender}": "{$sender}_{$receive}";
        Redis::hset(self::lastChatCacheKey($sender),$key,$message);
    }

    /**
     * 获取好友之间或群聊中发送的最后一条消息缓存
     *
     * @param int $receive 接收者
     * @param int $sender  发送者(注：若聊天消息类型为群聊消息 $sender 应设置为0)
     * @return mixed
     */
    public static function getLastChatCache(int $receive,$sender = 0){
        $key = $receive < $sender ? "{$receive}_{$sender}": "{$sender}_{$receive}";
        return Redis::hget(self::lastChatCacheKey($sender),$key);
    }
}
