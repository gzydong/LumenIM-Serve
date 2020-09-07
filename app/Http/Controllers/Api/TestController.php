<?php
namespace App\Http\Controllers\Api;

use App\Helpers\Jwt\Jwt;
use App\Models\Emoticon;
use Illuminate\Http\Request;


/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(Request $request)
    {
        $jwtObject = Jwt::getInstance()
            ->setSecretKey('easyswoole') // 秘钥
            ->publish();

        $jwtObject->setAlg('HMACSHA256'); // 加密方式
        $jwtObject->setAud('user'); // 用户
        $jwtObject->setExp(time()+3600); // 过期时间
        $jwtObject->setIat(time()); // 发布时间
        $jwtObject->setIss('easyswoole'); // 发行人
        $jwtObject->setJti(md5(time())); // jwt id 用于标识该jwt
        $jwtObject->setNbf(time()+60*5); // 在此之前不可用
        $jwtObject->setSub('主题'); // 主题

// 自定义数据
        $jwtObject->setData([
            'other_info'
        ]);
//
//
//// 最终生成的token
//        echo $token = $jwtObject->__toString();

        $token = $request->get('token');
        $model = Jwt::getInstance()->decode($token);
        var_dump($model->getStatus());
    }

    public function index(Request $request)
    {

    }
}


