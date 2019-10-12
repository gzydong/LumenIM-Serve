<?php
namespace App\Http\Controllers\Api;

use App\Logic\UsersLogic;
use Illuminate\Support\Facades\App;
use SwooleTW\Http\Server\Facades\Server;

use App\Helpers\WebSocketHelper;



class ChatController extends CController
{

    /**
     * 聊天用户记录列表
     */
    public function userRecords(WebSocketHelper $webSocketHelper){

        $webSocketHelper->getUserFd(45);
        dd($webSocketHelper->getUserFd(45));
    }


}
