<?php

namespace App\Http\Controllers\Api;

use App\Logic\TalkLogic;
use App\Models\Article;
use App\Models\ArticleClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(TalkLogic $talkLogic)
    {
        $data = $talkLogic->getForwardRecords(2054,4);
        dd($data);
    }

    public function index(Request $request)
    {

    }
}


