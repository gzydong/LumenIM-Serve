<?php
namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Mail;


use App\Models\UsersFriends;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){
        $ids = UsersFriends::getFriendIds(2054);
        dd($ids);
    }
}
