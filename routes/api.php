<?php

$router->get('/', ['as' => 'api', function () {
    return response()->json(['code'=>200,'msg'=>'SUCCESS','data'=>['username'=>'测试']]);
}]);





//AuthController 控制器分组
$router->group([],function () use ($router) {
    $router->post('/auth/login', ['middleware'=>[],'uses' => 'AuthController@login']);
    $router->post('/auth/register', ['middleware'=>[],'uses' => 'AuthController@register']);
    $router->post('/auth/logout', ['middleware'=>[],'uses' => 'AuthController@logout']);
    $router->post('/auth/refresh-token', ['middleware'=>[],'uses' => 'AuthController@refreshToken']);
});


//UsersController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/user/friends', ['middleware'=>[],'uses' => 'UsersController@getUserFriends']);
    $router->get('/user/chat-list', ['middleware'=>[],'uses' => 'UsersController@getChatList']);
});



//ChatController 控制器分组
$router->group([],function () use ($router) {
//    $router->get('/caht/user-records', ['middleware'=>[],'uses' => 'ChatController@userRecords']);
});




//ChatController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/test/index', ['middleware'=>[],'uses' => 'TestController@index']);
});