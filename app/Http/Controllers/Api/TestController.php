<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\UsersFriends;

use App\Facades\ChatService;
use App\Helpers\RsaMeans;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request){


        $data = RsaMeans::encrypt('use App\Helpers\RsaMeans;safa sdfas dfasdfasdfa sdfas dfasdf');
        echo strlen($data),' : '.$data;
        echo '<br/>';

        dd(RsaMeans::decrypt($data));

        exit;
        $json = '{"sourceType":"1","receiveUser":"2054","sendUser":"2053","msgType":1,"textMessage":"222222","imgMessage":"","fileMessage":""}';
        $data = json_decode($json,true);

        $data['created_at'] = date('Y-m-d H:i:s');
        ChatService::saveChatRecord($data);


        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
