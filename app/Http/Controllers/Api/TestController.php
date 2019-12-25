<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ImageCompose;

/**
 * 测试控制器
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController
{

    public function index()
    {
        $images = [
            'http://47.105.180.123:84/static/image/touxiang/u=184803235,1555534009&fm=111&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=1152472852,1674093815&fm=26&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=1225597740,615370700&fm=111&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=1441588315,1666293982&fm=26&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=1640434779,3971610929&fm=26&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=2369017631,3935728806&fm=26&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=2575047779,2966283883&fm=26&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=2727406657,464041112&fm=111&gp=0.jpg',
            'http://47.105.180.123:84/static/image/touxiang/u=3256100974,305075936&fm=26&gp=0.jpg',
        ];

       $object = new ImageCompose($images);
       $object->compose();
       $object->renderImage();
    }
}
