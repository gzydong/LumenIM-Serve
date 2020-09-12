<?php
return [
    // Web 端首页地址
    'web_url' => 'http://im.gzydong.club',

    //设置文件图片访问的域名
    'file_domain' => 'http://im-img0.gzydong.club',

    'swoole_proxy' => [
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
