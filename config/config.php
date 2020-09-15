<?php
return [
    // 域名相关配置
    'domain'=>[
        'web_url' => 'http://im.gzydong.club',// Web 端首页地址
        'img_url' => 'http://im-img.gzydong.club',//设置文件图片访问的域名
    ],

    // Swoole 配置信息
    'proxy' => [
        'host' => '127.0.0.1',
        'port' => '9501'
    ],

    // jwt 授权相关配置
    'jwt' => [
        'algo' => 'HS256',// HS256, HMACSHA256, AES
        'secret' => env('JWT_SECRET', ''),
        'ttl' => 60 * 60 * 24 * 7,// 过期时间
    ]
];
