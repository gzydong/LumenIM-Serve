<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use Illuminate\Http\Request;
use App\Logic\UsersLogic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\Local;

use App\Helpers\Cache\CacheHelper;
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){
       $num =  CacheHelper::getChatUnreadNum(2054,2055);
       dd($num);
    }
}
