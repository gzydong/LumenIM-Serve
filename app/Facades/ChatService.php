<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class ChatService
 * @package App\Facades
 *
 * @method static \App\Services\Socket checkFriends(int $send_user_id, int $receive_user_id)
 * @method static \App\Services\Socket getChatMessage(int $send_user, int $receive_user, int $source_type, int $msg_type, array $data)
 */
class ChatService extends Facade
{
    /**
     * 获取组件的注册名称。
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'chat:service';
    }
}
