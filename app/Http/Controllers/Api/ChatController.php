<?php
namespace App\Http\Controllers\Api;

use App\Logic\UsersLogic;
use App\Helpers\WebSocketHelper;



class ChatController extends CController
{

    /**
     * 聊天用户记录列表
     */
    public function userRecords(WebSocketHelper $webSocketHelper){

        $ids = UsersLogic::getUserGroupIds(1017);

        $rooms = array_map(function ($group_id){
            return "room.group.chat.{$group_id}";
        },$ids);
        dd($rooms);
    }


}
