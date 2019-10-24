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
    $router->post('/auth/logout', ['middleware'=>['jwt.auth'],'uses' => 'AuthController@logout']);
    $router->post('/auth/refresh-token', ['middleware'=>['jwt.auth'],'uses' => 'AuthController@refreshToken']);
});

//UsersController 控制器分组
$router->group(['middleware'=>['jwt.auth']],function () use ($router) {
    $router->get('/user/friends', ['uses' => 'UsersController@getUserFriends']);
    $router->post('/user/edit-nickname', ['uses' => 'UsersController@editNickname']);
    $router->post('/user/change-password', ['uses' => 'UsersController@changePassword']);
    $router->post('/user/edit-avatar', ['uses' => 'UsersController@editAvatar']);

    $router->get('/user/friend-apply-records', ['uses' => 'UsersController@getFriendApplyRecords']);
    $router->post('/user/send-friend-apply', ['uses' => 'UsersController@sendFriendApply']);
    $router->post('/user/handle-friend-apply', ['uses' => 'UsersController@handleFriendApply']);
    $router->post('/user/edit-friend-remark', ['uses' => 'UsersController@editFriendRemark']);


    $router->post('/user/search-user', ['uses' => 'UsersController@searchUserInfo']);
});

//ChatController 控制器分组
$router->group(['middleware'=>['jwt.auth']],function () use ($router) {
    $router->get('/chat/chat-list', ['uses' => 'ChatController@getChatList']);
    $router->get('/chat/chat-records', ['uses' => 'ChatController@getChatRecords']);


    //群聊相关接口
    $router->post('/chat/launch-group-chat', ['uses' => 'ChatController@launchGroupChat']);
    $router->post('/chat/invite-group-chat', ['uses' => 'ChatController@inviteGroupChat']);
    $router->post('/chat/remove-group-chat', ['uses' => 'ChatController@removeGroupChat']);
    $router->post('/chat/dismiss-group-chat', ['uses' => 'ChatController@dismissGroupChat']);

    $router->post('/chat/create-chat-list', ['uses' => 'ChatController@createChatList']);
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
