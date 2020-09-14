<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;


/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(Request $request)
    {
        get_media_url();
    }

    public function index(Request $request)
    {

    }
}
