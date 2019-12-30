<?php
namespace App\Http\Controllers\Api;

use App\Logic\ChatLogic;
use App\Models\UsersGroup;

use Intervention\Image\ImageManagerStatic as Image;
//https://blog.csdn.net/qq_34694342/article/details/83146100
/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{
    public function index(ChatLogic $chatLogic)
    {

        file_put_contents('D:/phpStudy/PHPTutorial/WWW/lumenim/uploads/ttt.gif',file_get_contents('http://q.qqbiaoqing.com/q/2017/11/10/df4f3582927c9683adf300e12c4b4d32.gif'));

//        $img = Image::make(file_get_contents('http://q.qqbiaoqing.com/q/2017/11/10/df4f3582927c9683adf300e12c4b4d32.gif'))->resize(200, 200);
////
////        // 将处理后的图片重新保存到其他路径
//        $img->save('D:/phpStudy/PHPTutorial/WWW/lumenim/uploads/df4f3582927c9683adf300e12c4b4d32.gif');
    }
}
