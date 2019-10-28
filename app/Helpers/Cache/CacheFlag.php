<?php
namespace App\Helpers\Cache;

/**
 * 系统缓存标识管理类
 *
 * @package App\Helpers\Cache
 */
class CacheFlag
{
    public static function friendsChatKey(){
        return 'friends.chat.last.msg';
    }

    public static function groupsChatKey(){
        return 'groups.chat.last.msg';
    }
}
