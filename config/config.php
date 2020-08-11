<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/20
 * Time: 15:11
 */
return [
    //设置文件图片访问的域名
    'file_domain'=>'http://47.105.180.123:5000',

    'jwt_secret' => env('JWT_SECRET',''),

    'swoole_proxy'=>[
        'host'=>'127.0.0.1',
        'port'=>'9501'
    ],

    'web_url'=>'http://im.gzydong.club',
];
