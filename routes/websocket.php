<?php


use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/


//
//Websocket::on('connect', function ($websocket, Request $request) {
//    echo '1 :called while socket on connect';
//    // called while socket on connect
//});

//Websocket::on('disconnect', function ($websocket) {
//    // called while socket on disconnect
//    echo '连接断开了'.PHP_EOL;
//});


//聊天对话消息处理
Websocket::on('event_chat_dialogue', 'App\Http\Controllers\SocketController@chatDialogue');

//键盘输入推送
Websocket::on('event_chat_input_tip', 'App\Http\Controllers\SocketController@inputTipPush');
