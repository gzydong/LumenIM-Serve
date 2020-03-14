<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;
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
