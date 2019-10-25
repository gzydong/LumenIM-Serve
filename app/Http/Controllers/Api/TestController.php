<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use Illuminate\Http\Request;
use App\Logic\UsersLogic;

use App\Facades\WebSocketHelper;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,UsersLogic $usersLogic){
        $rows = $usersLogic->getUserFriends(2054);
        if($rows){
            foreach ($rows as $k => $row){
                $rows[$k]->online = WebSocketHelper::getUserFds($row->id) ? 1 : 0;
            }
        }
    }
}
