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

//        dd(array_diff([11,22,33],[11]));
//
//        $fds = WebSocketHelper::getUserFd(10115);
//        dd($fds);



        go(function () {
            $db = new Co\MySQL();
            $server = array(
                'host' => '127.0.0.1',
                'user' => 'root',
                'password' => 'yuandong_1hblsqt',
                'database' => 'lumen-im',
            );

            $db->connect($server);

            $result = $db->query('SELECT * FROM lar_users WHERE id = 1017');
            var_dump($result);
        });


        exit;

        $sid = $request->get('sid','');
        return view('test.index',['sid'=>$sid]);
    }
}
