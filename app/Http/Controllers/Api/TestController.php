<?php

namespace App\Http\Controllers\Api;

use App\Models\UsersEmoticon;
use App\Services\UserService;
use Illuminate\Http\Request;


/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(UserService $userService)
    {
        $userInfo = $userService->findById(2054,['mobile']);
        dd($userInfo);
    }

    public function index(Request $request)
    {
        echo 'asdfasd';
    }
}
