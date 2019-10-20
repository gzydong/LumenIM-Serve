<?php
namespace App\Http\Controllers\Api;

use App\Logic\FriendsLogic;
use App\Models\User;
use Illuminate\Http\Request;


/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,FriendsLogic $friendsLogic){
        $redis = app('redis.connection');

        dd($redis->hgetall('hash.fds.list'));

        exit;
        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
