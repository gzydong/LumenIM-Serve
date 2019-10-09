<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/23
 * Time: 11:13
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */
    'supportsCredentials' => false,
    'allowedOrigins' => ['*'],
    'allowedHeaders' => ['Content-Type', 'X-Requested-With','Authorization'],
    'allowedMethods' => ['*'], // ex: ['GET', 'POST', 'PUT',  'DELETE']
    'exposedHeaders' => [],
    'maxAge' => 60*60*1,//Access-Control-Max-Age 字段指定了预检请求的结果能够被缓存多久，单位秒
];