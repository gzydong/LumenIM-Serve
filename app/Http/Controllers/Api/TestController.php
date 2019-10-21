<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use App\Models\User;
use Illuminate\Http\Request;
use App\Facades\WebSocketHelper;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,ChatLogic $chatLogic){

//        dd(array_diff([11,22,33],[11]));
//
//        $fds = WebSocketHelper::getUserFd(10115);
//        dd($fds);

        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
