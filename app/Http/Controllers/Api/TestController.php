<?php
namespace App\Http\Controllers\Api;


use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;

use App\Helpers\Cache\CacheHelper;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){


       $res =  User::select('id', 'nickname')->whereIn('id', [2054,2055])->get()->toArray();
       dd($res);
    }
}
