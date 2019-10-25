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
     * 设置好友之间发送的最后一条消息缓存
     *
     * @param int $user1 用户1
     * @param int $user2 用户2
     * @param string $message
     */
    public static function setFriendsChatCache($user1,$user2,string $message){
        $key = $user1 < $user2 ? "{$user1}_{$user2}": "{$user2}_{$user1}";
        Redis::hset(self::friendsChatKey(),$key,$message);
    }

    /**
     * 获取好友之间发送的最后一条消息内容
     *
     * @param int $user1 用户1
     * @param int $user2 用户2
     * @return mixed
     */
    public static function getFriendsChatCache($user1,$user2){
        $key = $user1 < $user2 ? "{$user1}_{$user2}": "{$user2}_{$user1}";
        return Redis::hget(self::friendsChatKey(),$key);
    }

    /**
     * 设置群聊中最后一次发送消息的缓存
     *
     * @param int $group_id 群聊ID
     * @return mixed
     */
    public static function setGroupsChatCache(int $group_id){
        return Redis::hget(self::groupsChatKey(),$group_id);
    }

    /**
     * 获取群聊中最后一条发送的消息内容
     *
     * @param int $group_id 群聊ID
     */
    public static function getGroupsChatCache(int $group_id){
        Redis::hget(self::groupsChatKey(),$group_id) ? : '';
    }

    //-------------------------------



}
