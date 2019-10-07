<?php

$router->get('/', ['as' => 'api', function () {
    return response()->json(['code'=>200,'msg'=>'SUCCESS','data'=>['username'=>'测试']]);
}]);


//UsersController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/user/friends', ['middleware'=>[],'uses' => 'UsersController@getUserFriends']);
});


//AuthController 控制器分组
$router->group([],function () use ($router) {
    $router->post('/auth/login', ['middleware'=>[],'uses' => 'AuthController@login']);
    $router->post('/auth/register', ['middleware'=>[],'uses' => 'AuthController@register']);
    $router->post('/auth/logout', ['middleware'=>[],'uses' => 'AuthController@logout']);
    $router->post('/auth/refresh-token', ['middleware'=>[],'uses' => 'AuthController@refreshToken']);
});