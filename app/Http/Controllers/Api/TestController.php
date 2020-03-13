<?php
namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use App\Models\EmoticonDetails;

use App\Logic\ArticleLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{


    public function index(Request $request)
    {

//        dd(Auth::guard('api')->check());
//        $exp = JWTAuth::parseToken()->payload();

        $token = JWTAuth::parseToken()->getToken();
        dd($token);
//        $ip = '255.255.255.250';
//        $res = ip2long($ip);
//
////        $request->getClientIp();
//
//        dd($request->getClientIp());
//
//
//
//        dd(long2ip(4294967295));
//
//        $ip2 = long2ip($res);
//
//        dd($ip,$res,$ip2);
        exit;
        $list = DB::table('article_test')->select(['title','describe','content','markdown_content'])->where('status',1)->get();
        $logic = new ArticleLogic();

        foreach ($list as $v){
            $logic->editArticle(2054,0,[
                'title'=>$v->title,
                'abstract'=>mb_substr(strip_tags(htmlspecialchars_decode($v->content)),0,200),
                'class_id'=>mt_rand(1,6),
                'md_content'=>$v->markdown_content,
                'content'=>$v->content
            ]);
        }

        exit;
        $page = $request->get('page',1);
        $items = EmoticonDetails::where('describe','=','')->forPage($page, 1000)->get()->toArray();

        return response()->json(['code' => 200, 'msg' => 'success', 'data' => $items]);
    }

    public function index2(Request $request)
    {
        $id = $request->post('id',1);
        $describe = $request->post('describe','');

        if(empty($describe)){
            return response()->json(['code' => 305, 'msg' => 'success', 'data' => []]);
        }

        if(EmoticonDetails::where('id',$id)->update(['describe'=>$describe])){
            return response()->json(['code' => 200, 'msg' => 'success', 'data' => []]);
        }else{
            return response()->json(['code' => 305, 'msg' => 'success', 'data' => []]);
        }
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
