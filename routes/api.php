<?php

$router->get('/', ['as' => 'api', function () {

    return response()->json(['code'=>200,'msg'=>'SUCCESS','data'=>['username'=>'测试']]);
}]);


//UsersController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/user/test', ['middleware'=>[],'uses' => 'UsersController@test']);
});


//AuthController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/auth/login', ['middleware'=>[],'uses' => 'AuthController@login']);
    $router->get('/auth/register', ['middleware'=>[],'uses' => 'AuthController@register']);
});