<?php

namespace App\Http\Controllers\Api;

use App\Facades\SocketResourceHandle;
use App\Helpers\MobileInfo;
use App\Helpers\RedisLock;
use App\Helpers\SendEmailCode;
use App\Logic\ArticleLogic;
use App\Logic\GroupLogic;
use App\Models\User;
use App\Models\UsersFriends;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;

set_time_limit(0);

/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(GroupLogic $groupLogic)
    {
        $user_id = '2054_user';
        $result = RedisLock::lock($user_id,0,20);
        dd($result);
    }

    public function index(Request $request)
    {

    }
}
