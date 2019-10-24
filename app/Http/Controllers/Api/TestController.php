<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use Illuminate\Http\Request;
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(Request $request,ChatLogic $chatLogic){

        $data = $chatLogic->getPrivateChatInfos(0,2053,2054);

        dd($data);
    }
}
