<?php
namespace App\Http\Controllers\Api;

use App\Logic\UsersLogic;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UsersFriends;

use App\Facades\ChatService;
use App\Helpers\RsaMeans;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,UsersLogic $usersLogic){

        $data = $usersLogic->searchUserInfo('18969249284',2054);
        dd($data);

        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
