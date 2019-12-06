<?php

/**
 * Api 接口路由配置
 */
$router->get('/', ['as' => 'api', function () {
    return response()->json(['code' => 305, 'msg' => '非法请求...']);
}]);


//AuthController 控制器分组
$router->group([], function () use ($router) {
    $router->post('/auth/login', ['middleware' => [], 'uses' => 'AuthController@login']);
    $router->post('/auth/register', ['middleware' => [], 'uses' => 'AuthController@register']);
    $router->post('/auth/logout', ['middleware' => ['jwt.auth'], 'uses' => 'AuthController@logout']);
    $router->get('/auth/refresh-token', ['middleware' => [], 'uses' => 'AuthController@refreshToken']);
});

//UsersController 控制器分组
$router->group(['middleware' => ['jwt.auth']], function () use ($router) {
    $router->get('/user/friends', ['uses' => 'UsersController@getUserFriends']);
    $router->get('/user/user-groups', ['uses' => 'UsersController@getUserGroups']);
    $router->get('/user/detail', ['uses' => 'UsersController@getUserDetail']);
    $router->post('/user/edit-user-detail', ['uses' => 'UsersController@editUserDetail']);
    $router->post('/user/change-password', ['uses' => 'UsersController@changePassword']);
    $router->post('/user/edit-avatar', ['uses' => 'UsersController@editAvatar']);
    $router->post('/user/search-user', ['uses' => 'UsersController@searchUserInfo']);
    $router->post('/user/edit-friend-remark', ['uses' => 'UsersController@editFriendRemark']);
    $router->post('/user/send-friend-apply', ['uses' => 'UsersController@sendFriendApply']);
    $router->post('/user/handle-friend-apply', ['uses' => 'UsersController@handleFriendApply']);
    $router->get('/user/friend-apply-records', ['uses' => 'UsersController@getFriendApplyRecords']);
    $router->get('/user/friend-apply-num', ['uses' => 'UsersController@getApplyUnreadNum']);
});


//ChatController 控制器分组
$router->group(['middleware' => ['jwt.auth']], function () use ($router) {
    $router->get('/chat/chat-list', ['uses' => 'ChatController@getChatList']);
    $router->get('/chat/chat-records', ['uses' => 'ChatController@getChatRecords']);


    //群聊相关接口
    $router->post('/chat/launch-group-chat', ['uses' => 'ChatController@launchGroupChat']);
    $router->post('/chat/invite-group-chat', ['uses' => 'ChatController@inviteGroupChat']);
    $router->post('/chat/remove-group-chat', ['uses' => 'ChatController@removeGroupChat']);
    $router->post('/chat/dismiss-group-chat', ['uses' => 'ChatController@dismissGroupChat']);
    $router->post('/chat/quit-group-chat', ['uses' => 'ChatController@quitGroupChat']);
    $router->post('/chat/set-group-card', ['uses' => 'ChatController@setGroupCard']);
    $router->get('/chat/get-chat-member', ['uses' => 'ChatController@getChatMember']);
    $router->post('/chat/create-chat-list', ['uses' => 'ChatController@createChatList']);
    $router->get('/chat/group-detail', ['uses' => 'ChatController@getGroupDetail']);
    $router->get('/chat/update-chat-unread-num', ['uses' => 'ChatController@updateChatUnreadNum']);

    $router->post('/chat/set-group-disturb', ['uses' => 'ChatController@setGroupDisturb']);
});

//UploadController 上传文件控制器分组
$router->group(['middleware' => ['jwt.auth']], function () use ($router) {
    $router->post('/upload/img', ['uses' => 'UploadController@img']);
    $router->post('/upload/file', ['uses' => 'UploadController@file']);
    $router->post('/upload/file-stream', ['uses' => 'UploadController@fileStream']);
});

//TestController 控制器分组
$router->group([], function () use ($router) {
    $router->get('/test/index', ['middleware' => [], 'uses' => 'TestController@index']);
});
