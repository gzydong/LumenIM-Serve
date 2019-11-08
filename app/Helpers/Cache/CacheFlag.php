<?php
namespace App\Helpers\Cache;

/**
 * 系统缓存标识管理类
 *
 * @package App\Helpers\Cache
 */
class CacheFlag
{
    public static function lastChatCacheKey($sender = 0){
        return $sender == 0 ? 'groups.chat.last.msg' : 'friends.chat.last.msg';
    }


    public static function chatUnreadNumCacheKey(){
        return  'hash.chat.unread.num';
    }
}
