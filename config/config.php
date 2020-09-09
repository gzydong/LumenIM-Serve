<?php
return [
    //设置文件图片访问的域名
    'file_domain'=>'http://im-img.gzydong.club',

    'jwt_secret' => env('JWT_SECRET',''),

    'swoole_proxy'=>[
        'host'=>'127.0.0.1',
        'port'=>'9501'
    ],

    'jwt'=>[
        'secret'=>env('JWT_SECRET',''),
        'ttl'=>60*60*24*7
    ],

    'web_url'=>'http://im.gzydong.club',
];
