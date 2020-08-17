<?php

/**
 * Api 接口路由配置
 */
$router->get('/', ['as' => 'api', function () {
    return response()->json(['code' => 305, 'msg' => '非法请求...']);
}]);

/**
 * AuthController 控制器分组
 */
$router->group([], function () use ($router) {
    $router->post('auth/login', ['middleware' => [], 'uses' => 'AuthController@login']);
    $router->post('auth/register', ['middleware' => [], 'uses' => 'AuthController@register']);
    $router->post('auth/logout', ['middleware' => ['jwt'], 'uses' => 'AuthController@logout']);

    $router->post('auth/send-verify-code', ['middleware' => [], 'uses' => 'AuthController@sendVerifyCode']);
    $router->post('auth/forget-password', ['middleware' => [], 'uses' => 'AuthController@forgetPassword']);
});

/**
 * UsersController 控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->get('user/friends', ['uses' => 'UsersController@getUserFriends']);
    $router->post('user/remove-friend', ['uses' => 'UsersController@removeFriend']);
    $router->get('user/user-groups', ['uses' => 'UsersController@getUserGroups']);
    $router->get('user/detail', ['uses' => 'UsersController@getUserDetail']);
    $router->get('user/setting', ['uses' => 'UsersController@getUserSetting']);
    $router->post('user/edit-user-detail', ['uses' => 'UsersController@editUserDetail']);

    $router->post('user/edit-avatar', ['uses' => 'UsersController@editAvatar']);
    $router->post('user/search-user', ['uses' => 'UsersController@searchUserInfo']);
    $router->post('user/edit-friend-remark', ['uses' => 'UsersController@editFriendRemark']);
    $router->post('user/send-friend-apply', ['uses' => 'UsersController@sendFriendApply']);
    $router->post('user/handle-friend-apply', ['uses' => 'UsersController@handleFriendApply']);
    $router->get('user/friend-apply-records', ['uses' => 'UsersController@getFriendApplyRecords']);
    $router->get('user/friend-apply-num', ['uses' => 'UsersController@getApplyUnreadNum']);

    $router->post('user/change-password', ['uses' => 'UsersController@editUserPassword']);
    $router->post('user/change-mobile', ['uses' => 'UsersController@editUserMobile']);
    $router->post('user/change-email', ['uses' => 'UsersController@editUserEmail']);

    $router->post('user/send-mobile-code', ['uses' => 'UsersController@sendMobileCode']);
    $router->post('user/send-change-email-code', ['uses' => 'UsersController@sendChangeEmailCode']);
});

/**
 * TalkController 控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->post('talk/create', ['uses' => 'TalkController@create']);
    $router->post('talk/delete', ['uses' => 'TalkController@delete']);
    $router->post('talk/topping', ['uses' => 'TalkController@topping']);
    $router->post('talk/set-not-disturb', ['uses' => 'TalkController@setNotDisturb']);
    $router->get('talk/list', ['uses' => 'TalkController@list']);
    $router->post('talk/update-unread-num', ['uses' => 'TalkController@updateUnreadNum']);

    $router->get('talk/records', ['uses' => 'TalkController@getChatRecords']);
    $router->post('talk/revoke-records', ['uses' => 'TalkController@revokeChatRecords']);
    $router->post('talk/remove-records', ['uses' => 'TalkController@removeChatRecords']);
    $router->post('talk/forward-records', ['uses' => 'TalkController@forwardChatRecords']);
    $router->get('talk/get-forward-records', ['uses' => 'TalkController@getForwardRecords']);

    $router->post('talk/upload-talk-img', ['uses' => 'TalkController@uploadTaklImg']);

    $router->get('talk/find-chat-records', ['uses' => 'TalkController@findChatRecords']);
    $router->get('talk/search-chat-records', ['uses' => 'TalkController@searchChatRecords']);
    $router->get('talk/get-records-context', ['uses' => 'TalkController@getRecordsContext']);
});

/**
 * GroupController 用户群控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->post('group/create', ['uses' => 'GroupController@create']);
    $router->post('group/edit', ['uses' => 'GroupController@editDetail']);
    $router->post('group/invite', ['uses' => 'GroupController@invite']);
    $router->post('group/dismiss', ['uses' => 'GroupController@dismiss']);
    $router->post('group/secede', ['uses' => 'GroupController@secede']);

    $router->post('group/set-group-card', ['uses' => 'GroupController@setGroupCard']);
    $router->post('group/edit-notice', ['uses' => 'GroupController@editNotice']);
    $router->post('group/delete-notice', ['uses' => 'GroupController@deleteNotice']);
    $router->post('group/remove-members', ['uses' => 'GroupController@removeMembers']);

    $router->get('group/detail', ['uses' => 'GroupController@detail']);
    $router->get('group/invite-friends', ['uses' => 'GroupController@getInviteFriends']);
    $router->get('group/members', ['uses' => 'GroupController@getGroupMembers']);
    $router->get('group/notices', ['uses' => 'GroupController@getGroupNotices']);
});

/**
 * UploadController 上传文件控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->post('upload/file-stream', ['uses' => 'UploadController@fileStream']);
    $router->post('upload/file-subarea-upload', ['uses' => 'UploadController@fileSubareaUpload']);
    $router->get('upload/get-file-split-info', ['uses' => 'UploadController@getFileSplitInfo']);
});

/**
 * EmoticonController 表情包控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->get('emoticon/user-emoticon', ['uses' => 'EmoticonController@getUserEmoticon']);
    $router->get('emoticon/system-emoticon', ['uses' => 'EmoticonController@getSystemEmoticon']);
    $router->post('emoticon/set-user-emoticon', ['uses' => 'EmoticonController@setUserEmoticon']);
    $router->post('emoticon/upload-emoticon', ['uses' => 'EmoticonController@uploadEmoticon']);
    $router->post('emoticon/collect-emoticon', ['uses' => 'EmoticonController@collectEmoticon']);
    $router->post('emoticon/del-collect-emoticon', ['uses' => 'EmoticonController@delCollectEmoticon']);
});

/**
 * DownloadController 下载文件控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->get('download/user-chat-file', ['uses' => 'DownloadController@userChatFile']);
    $router->get('download/article-annex', ['uses' => 'DownloadController@articleAnnex']);
});

/**
 * ArticleController控制器分组
 */
$router->group(['middleware' => ['jwt']], function () use ($router) {
    $router->get('article/article-class', ['uses' => 'ArticleController@getArticleClass']);
    $router->get('article/article-tags', ['uses' => 'ArticleController@getArticleTags']);
    $router->get('article/article-list', ['uses' => 'ArticleController@getArticleList']);
    $router->get('article/article-detail', ['uses' => 'ArticleController@getArticleDetail']);

    //笔记分类
    $router->post('article/edit-article-class', ['uses' => 'ArticleController@editArticleClass']);
    $router->post('article/del-article-class', ['uses' => 'ArticleController@delArticleClass']);
    $router->post('article/article-class-sort', ['uses' => 'ArticleController@articleClassSort']);
    $router->post('article/merge-article-class', ['uses' => 'ArticleController@mergeArticleClass']);

    //笔记标签
    $router->post('article/edit-article-tag', ['uses' => 'ArticleController@editArticleTags']);
    $router->post('article/del-article-tag', ['uses' => 'ArticleController@delArticleTags']);

    //笔记相关接口
    $router->post('article/edit-article', ['uses' => 'ArticleController@editArticle']);
    $router->post('article/delete-article', ['uses' => 'ArticleController@deleteArticle']);
    $router->post('article/recover-article', ['uses' => 'ArticleController@recoverArticle']);
    $router->post('article/upload-article-image', ['uses' => 'ArticleController@uploadArticleImage']);
    $router->post('article/move-article', ['uses' => 'ArticleController@moveArticle']);
    $router->post('article/set-asterisk-article', ['uses' => 'ArticleController@setAsteriskArticle']);
    $router->post('article/update-article-tag', ['uses' => 'ArticleController@updateArticleTag']);
    $router->post('article/forever-delete-article', ['uses' => 'ArticleController@foreverDelArticle']);

    //笔记附件
    $router->post('article/upload-article-annex', ['uses' => 'ArticleController@uploadArticleAnnex']);
    $router->post('article/delete-article-annex', ['uses' => 'ArticleController@deleteArticleAnnex']);
    $router->post('article/recover-article-annex', ['uses' => 'ArticleController@recoverArticleAnnex']);
    $router->get('article/recover-annex-list', ['uses' => 'ArticleController@recoverAnnexList']);
    $router->post('article/forever-delete-annex', ['uses' => 'ArticleController@foreverDelAnnex']);
});


/**
 * 测试控制器
 */
$router->group([], function () use ($router) {
    $router->get('test/test', ['middleware' => [], 'uses' => 'TestController@test']);
});
