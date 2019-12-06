<?php
namespace App\Http\Controllers\Api;


use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;

use App\Helpers\Cache\CacheHelper;

use Illuminate\Support\Facades\Redis;
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){
        dd(Redis::hlen('hash.user.friend.remark.cache'));
        dd(Redis::expire('hash.user.friend.remark.cache',60));
    }
}
