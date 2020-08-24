<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Socket\NotifyInterface;
use App\Logic\TalkLogic;
use App\Models\Article;
use App\Models\ArticleClass;
use App\Models\ChatRecordsForward;
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
        $data = NotifyInterface::formatTalkMsg([]);

        $str = $this->StrToBin(json_encode($data));
        dd($this->BinToStr($str));
    }


    /**
     * 将字符串转换成二进制
     * @param type $str
     * @return type
     */
    function StrToBin($str)
    {
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach ($arr as &$v) {
            $temp = unpack('H*', $v);
            $v = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join(' ', $arr);
    }

    /**
     * 将二进制转换成字符串
     * @param type $str
     * @return type
     */
    function BinToStr($str)
    {
        $arr = explode(' ', $str);
        foreach ($arr as &$v) {
            $v = pack("H" . strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
        }
        return join('', $arr);
    }

    public function index(Request $request)
    {

    }

    function createCode($user_id)
    {

        static $source_string = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';

        $num = $user_id;

        $code = '';

        while ($num > 0) {

            $mod = $num % 35;

            $num = ($num - $mod) / 35;

            $code = $source_string[$mod] . $code;

        }

        if (empty($code[3]))

            $code = str_pad($code, 4, '0', STR_PAD_LEFT);

        return $code;

    }
}


