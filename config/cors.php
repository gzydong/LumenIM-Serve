<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/23
 * Time: 11:13
 */

return [
    'allow-credentiails' => env('CORS_ALLOW_CREDENTIAILS', false), // set "Access-Control-Allow-Credentials" ? string "false" or "true".
    'allow-headers'      => ['*'], // ex: Content-Type, Accept, X-Requested-With
    'expose-headers'     => [],
    'origins'            => ['*'], // ex: http://localhost
    'methods'            => ['*'], // ex: GET, POST, PUT, PATCH, DELETE
    'max-age'            => env('CORS_ACCESS_CONTROL_MAX_AGE', 0),
];