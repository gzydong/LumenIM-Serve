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
        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}