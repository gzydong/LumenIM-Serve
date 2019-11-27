<?php
namespace App\Http\Controllers\Api;


use App\Models\UsersGroupMember;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){


        $res = UsersGroupMember::from('users_group_member as ugm')
            ->select(['users.nickname','users.avatarurl','ugm.visit_card'])
            ->leftJoin('users','users.id','=','ugm.user_id')
            ->where('ugm.group_id',38)->where('ugm.user_id',2055)
            ->first()->toArray();

        dd($res);

    }
}
