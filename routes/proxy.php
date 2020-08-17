<?php
/**
 * 内网代理接口路由配置(内网请求)
 */


//EventController 控制器分组
$router->group(['middleware' => []], function () use ($router) {
    $router->post('event/launch-group-chat', ['uses' => 'EventController@launchGroupChat']);
    $router->post('event/invite-group-members', ['uses' => 'EventController@inviteGroupMember']);
    $router->post('event/group-notify', ['uses' => 'EventController@groupNotify']);

    $router->post('event/revoke-records', ['uses' => 'EventController@revokeRecords']);
    $router->post('event/forward-chat-records', ['uses' => 'EventController@forwardChatRecords']);
});
