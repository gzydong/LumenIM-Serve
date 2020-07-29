<?php

namespace App\Http\Controllers\Api;

use App\Facades\SocketResourceHandle;
use App\Logic\ArticleLogic;
use App\Models\User;
use App\Models\UsersFriends;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

set_time_limit(0);

/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(ArticleLogic $articleLogic,Request $request)
    {
        Log::info('--------');
    }

    public function index(Request $request)
    {

    }
}
