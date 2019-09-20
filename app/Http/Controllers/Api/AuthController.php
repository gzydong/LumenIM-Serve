<?php
namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Redis;

use App\Model\Config;

/**
 * 接口授权登录控制器
 * Class AuthController
 * @package App\Http\Controllers\Api
 */
class AuthController extends CController
{

    public function login(){
        dd(config('database'));

        dd(Config::where('config_name','asdfas')->get()->toArray());
    }
}