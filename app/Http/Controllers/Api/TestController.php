<?php
namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Mail;
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){
        //return view('emails.verify-code',['service_name'=>'重置密码','sms_code'=>654852,'domain'=>'http://47.105.180.123:83']);
        $res = Mail::send('emails.verify-code',['service_name'=>'重置密码','sms_code'=>654852,'domain'=>'http://47.105.180.123:83'], function($message){
            $message->to('837215079@qq.com', '我')->subject('On-line IM 重置密码(验证码)');
        });

        dd($res);
    }
}
