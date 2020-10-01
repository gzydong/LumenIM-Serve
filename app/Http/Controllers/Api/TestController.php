<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ArticleValidate;
use Illuminate\Http\Request;

/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{

    public function __construct()
    {

    }

    public function test(ArticleValidate $articleValidate, Request $request)
    {

        $type = $request->post('type');


        dd(check_int($type));
        $result = $articleValidate->check(app('request')->all());
        dd($articleValidate->getError());
    }
}
