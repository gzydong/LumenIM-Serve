<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Models\UsersFriends;


/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request){


        dd(UsersFriends::checkFriends(2064,15));

        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}