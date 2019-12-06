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


        $ids = explode(',','2054,2061,3063,3084,1149,2381');
       $res =  User::select('id', 'nickname')->whereIn('id',$ids)->get()->toArray();

        dd(customSort($res,$ids));
    }
}
