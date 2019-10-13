<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request){
        $data = [
            'sourceType'=>1,
            'receiveUser'=> 'toUserId',
            'sendUser'=> '',
            'msgType'=>1,
            'textMessage'=>'',
            'imgMessage'=>'',
            'fileMessage'=>'',
        ];
        sdf

        dd(array_has($data,['sourceType','receiveUser','sendUser','msgType','textMessage','imgMessage','fileMessage']));


        exit;
        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}