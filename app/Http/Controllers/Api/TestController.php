<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\EmoticonDetails;
use Illuminate\Http\Request;
use App\Helpers\Curl;

/**
 * 测试控制器
 * https://learnku.com/articles/10885/full-use-of-jwt
 *
 * https://www.cnblogs.com/liwei-17/p/9249546.html
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function test(){
        $port = config('swoole_http.server.port');
        $curl = new Curl();
        $curl->get("http://127.0.0.1:{$port}/api/test/index", ['username' => 'test']);
        $response = $curl->getBody();
        dd($response);
    }

    public function index(Request $request)
    {
        $userList = User::limit(10)->get()->toArray();
        return response()->json(['code' => 200, 'msg' => 'success', 'data' => ['users'=>$userList]]);
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
