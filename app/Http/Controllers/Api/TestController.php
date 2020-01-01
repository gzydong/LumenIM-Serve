<?php
namespace App\Http\Controllers\Api;

use App\Models\Emoticon;
use App\Models\UsersEmoticon;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index()
    {
        $info = UsersEmoticon::select(['id','user_id','emoticon_ids'])->where('user_id',2054)->first();
        dd($info->emoticon_ids);
    }

    public function getGif($href)
    {
        $str = file_get_contents($href);
        $pattern = "/<img.*?src=[\'|\"](http:\/\/pics.sc.chinaz.com\/Files\/pic\/faces\/.*?)[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $str, $result);
        preg_match('/<title>.*?<\/title>/ism', $str, $match);
        $title = explode('_', strip_tags($match[0]));
        return $result[1];
    }

    public function getHref($page)
    {
        $res = file_get_contents("http://sc.chinaz.com/biaoqing/index_{$page}.html");
        $rule = '/<a .*?href="http:\/\/sc.chinaz.com\/biaoqing\/(.*?)".*?>/is';
        preg_match_all($rule, $res, $result);
        return array_unique(array_filter($result[1]));
    }
}
