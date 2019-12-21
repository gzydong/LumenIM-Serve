<?php
namespace App\Http\Controllers\Api;

use App\Logic\FileSplitUploadLogic;


/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index(){
        $logic = new FileSplitUploadLogic();
//        2097152 101144293 49

        $data = $logic->createSplitInfo(1520,'redream-shop.zip','101144293');
        dd($data);
    }
}
