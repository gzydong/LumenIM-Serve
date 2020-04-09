<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/20
 * Time: 15:11
 */
return [
    'upload'=>[//上传文件相关配置
        'upload_domain'=>'http://47.105.180.123:5000'//上传文件域名
    ],

    'jwt_secret' => env('JWT_SECRET',''),
];
