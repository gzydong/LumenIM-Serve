<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use App\Models\User;
use Illuminate\Http\Request;
use App\Facades\WebSocketHelper;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,ChatLogic $chatLogic){
        echo time().PHP_EOL;
        for ($i=0;$i < 10;$i++){
            go(function () {
                sleep(1);
                var_dump(date('Y-m-d H:i:s'));
            });
        }

        echo time().PHP_EOL;


        exit;

        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
