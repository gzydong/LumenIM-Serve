<?php

namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use App\Models\UsersGroup;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(ChatLogic $chatLogic)
    {

        dd(emojiReplace('asdf'));

    }
}
