<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Socket\NotifyInterface;
use App\Logic\TalkLogic;
use App\Models\Article;
use App\Models\ArticleClass;
use App\Models\UsersFriends;
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

        $result = $talkLogic->getChatRecords(2054,2055,1,0);
        dd($result);
    }

    public function index(Request $request)
    {

    }

    function createCode($user_id) {

        static $source_string = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';

        $num = $user_id;

        $code = '';

        while ( $num > 0) {

            $mod = $num % 35;

            $num = ($num - $mod) / 35;

            $code = $source_string[$mod].$code;

        }

        if(empty($code[3]))

            $code = str_pad($code,4,'0',STR_PAD_LEFT);

        return $code;

    }
}


