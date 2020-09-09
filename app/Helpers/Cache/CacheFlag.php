<?php
namespace App\Helpers\Cache;

/**
 * 系统缓存标识管理类
 *
 * @package App\Helpers\Cache
 */
class CacheFlag
{
    /**
     * 用户聊天或群聊的最后一条消息hash存储的hash名
     *
     * @param int $sender
     * @return string
     */
    public static function lastChatCacheKey($sender = 0){
        return $sender == 0 ? 'groups:chat:last.msg' : 'friends:chat:last:msg';
    }

    /**
     * 好友申请未读数，hash存储名
     * @return string
     */
    public static function applyUnreadNumCacheKey(){
        return 'friend:apply:unread:num';
    }

    public static function userGroupVisitCardCacheKey(int $group_id){
        return "users:group:visit_card:{$group_id}";
    }

    /**
     * 好友备注缓存key
     *
     * @return string
     */
    public static function friendRemarkCacheKey(){
        return 'hash:user:friend:remark:cache';
    }
}
