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



//聊天对话消息处理
Websocket::on('event_talk', 'App\Http\Controllers\Socket\NotifyController@talk');
//键盘输入推送
Websocket::on('event_keyboard', 'App\Http\Controllers\Socket\NotifyController@keyboard');
