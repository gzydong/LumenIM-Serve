<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use App\Models\User;
use Illuminate\Http\Request;


/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,ChatLogic $chatLogic){


        dd('asd');



        $chatLogic->launchGroupChat(15,'测试群',[1513,11351,135135,4546,78914646]);

        exit;
        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
