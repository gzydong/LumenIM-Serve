<?php

/**
 * Api 接口路由配置
 */
$router->get('/', ['as' => 'api', function () {
    return response()->json(['code' => 305, 'msg' => '非法请求...']);
}]);


//测试控制器
$router->group([], function () use ($router) {
    $router->get('/test/index', ['middleware' => [], 'uses' => 'TestController@index']);
    $router->post('/test/index2', ['middleware' => [], 'uses' => 'TestController@index2']);
});

//AuthController 控制器分组
$router->group([], function () use ($router) {
    $router->post('/auth/login', ['middleware' => [], 'uses' => 'AuthController@login']);
    $router->post('/auth/register', ['middleware' => [], 'uses' => 'AuthController@register']);
    $router->post('/auth/logout', ['middleware' => ['myjwt'], 'uses' => 'AuthController@logout']);

    $router->post('/auth/send-verify-code', ['middleware' => [], 'uses' => 'AuthController@sendVerifyCode']);
    $router->post('/auth/forget-password', ['middleware' => [], 'uses' => 'AuthController@forgetPassword']);
});


//UsersController 控制器分组
$router->group(['middleware' => ['myjwt']], function () use ($router) {
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
$router->group(['middleware' => ['myjwt']], function () use ($router) {
    $router->get('/chat/chat-list', ['uses' => 'ChatController@getChatList']);
    $router->get('/chat/get-chat-item', ['uses' => 'ChatController@getChatItem']);
    $router->get('/chat/get-chats-records', ['uses' => 'ChatController@getChatsRecords']);
    $router->get('/chat/chat-files', ['uses' => 'ChatController@getChatFiles']);

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
    $router->get('/chat/find-chat-records', ['uses' => 'ChatController@findChatRecords']);
    $router->get('/chat/search-chat-records', ['uses' => 'ChatController@searchChatRecords']);


    //发送聊天图片
    $router->post('/chat/send-image', ['uses' => 'ChatController@uploadImage']);
});

//UploadController 上传文件控制器分组
$router->group(['middleware' => ['myjwt']], function () use ($router) {
    $router->post('/upload/file-stream', ['uses' => 'UploadController@fileStream']);
    $router->post('/upload/file-subarea-upload', ['uses' => 'UploadController@fileSubareaUpload']);
    $router->get('/upload/get-file-split-info', ['uses' => 'UploadController@getFileSplitInfo']);
});



//EmoticonController 表情包控制器分组
$router->group(['middleware' => ['myjwt']], function () use ($router) {
    $router->get('/emoticon/user-emoticon', ['uses' => 'EmoticonController@getUserEmoticon']);
    $router->get('/emoticon/system-emoticon', ['uses' => 'EmoticonController@getSystemEmoticon']);
    $router->post('/emoticon/set-user-emoticon', ['uses' => 'EmoticonController@setUserEmoticon']);
    $router->get('/emoticon/search-emoticon', ['uses' => 'EmoticonController@searchEmoticon']);
});

//DownloadController 下载文件控制器分组
$router->group(['middleware' => ['myjwt']], function () use ($router) {
    $router->get('/download/user-chat-file', ['uses' => 'DownloadController@userChatFile']);
});


//ArticleController控制器分组
$router->group(['middleware' => ['myjwt']], function () use ($router) {
    $router->get('/article/article-class', ['uses' => 'ArticleController@getArticleClass']);
    $router->get('/article/article-list', ['uses' => 'ArticleController@getArticleList']);
    $router->get('/article/article-detail', ['uses' => 'ArticleController@getArticleDetail']);
    $router->post('/article/edit-article', ['uses' => 'ArticleController@editArticle']);
    $router->post('/article/edit-article-class', ['uses' => 'ArticleController@editArticleClass']);
    $router->post('/article/del-article-class', ['uses' => 'ArticleController@delArticleClass']);
    $router->post('/article/article-class-sort', ['uses' => 'ArticleController@articleClassSort']);
    $router->post('/article/merge-article-class', ['uses' => 'ArticleController@mergeArticleClass']);
    $router->post('/article/upload-article-image', ['uses' => 'ArticleController@uploadArticleImage']);
});
