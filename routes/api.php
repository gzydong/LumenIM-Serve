<?php

/**
 * Api 接口路由配置
 */
$router->get('/', ['as' => 'api', function () {
    return response()->json(['code'=>305,'msg'=>'FAIL','data'=>['username'=>'非法请求...']]);
}]);

//AuthController 控制器分组
$router->group([],function () use ($router) {
    $router->post('/auth/login', ['middleware'=>[],'uses' => 'AuthController@login']);
    $router->post('/auth/register', ['middleware'=>[],'uses' => 'AuthController@register']);
    $router->post('/auth/logout', ['middleware'=>[],'uses' => 'AuthController@logout']);
    $router->post('/auth/refresh-token', ['middleware'=>[],'uses' => 'AuthController@refreshToken']);
});

//UsersController 控制器分组
$router->group(['middleware'=>['jwt.auth']],function () use ($router) {
    $router->get('/user/friends', ['uses' => 'UsersController@getUserFriends']);
    $router->post('/user/edit-nickname', ['uses' => 'UploadController@editNickname']);
    $router->post('/user/change-password', ['uses' => 'UploadController@changePassword']);
    $router->post('/user/edit-avatar', ['uses' => 'UploadController@editAvatar']);
});

//ChatController 控制器分组
$router->group(['middleware'=>[]],function () use ($router) {
    $router->get('/caht/chat-list', ['uses' => 'UsersController@getChatList']);
    $router->get('/caht/chat-records', ['middleware'=>[],'uses' => 'ChatController@getChatRecords']);
});

//UploadController 上传文件控制器分组
$router->group(['middleware'=>['jwt.auth']],function () use ($router) {
    $router->post('/upload/img', ['uses' => 'UploadController@img']);
    $router->post('/upload/file', ['uses' => 'UploadController@file']);
});

//TestController 控制器分组
$router->group([],function () use ($router) {
    $router->get('/test/index', ['middleware'=>[],'uses' => 'TestController@index']);
});
