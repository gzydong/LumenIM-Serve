<?php
namespace App\Http\Controllers\Api;

use App\Facades\WebSocketHelper;

use App\Helpers\Cache\CacheHelper;
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){
        dd(WebSocketHelper::getUserFds(3026));
    }
}
