<?php

$router->get('/', ['as' => 'api', function () {
    return '欢迎来到 LumenIm';
}]);




//AuthController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/auth/login', ['middleware'=>['jwt.auth'],'uses' => 'AuthController@login']);
});