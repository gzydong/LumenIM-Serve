<?php
return [
    // 域名相关配置
    'domain' => [
        'web_url' => env('WEB_URL', ''),// Web 端首页地址
        'img_url' => env('IMG_URL', ''),//设置文件图片访问的域名
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
    ],

    // SQL查询日志(测试环境)
    'sql_query_log' => [
        // 是否开启
        'enabled' => env('SQL_QUERY_LOG', false),
        // 慢查询时间/单位毫秒
        'slower_than' => env('SQL_QUERY_SLOWER', 0),
    ],
];
