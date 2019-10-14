<?php
namespace App\Http\Controllers\Api;

use App\Logic\UsersLogic;
use App\Logic\ChatLogic;

class UsersController extends CController
{

    /**
     * 获取用户好友列表
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFriends(UsersLogic $usersLogic){
        $rows = $usersLogic->getUserFriends($this->uid());
        return $this->ajaxSuccess('success',$rows);
    }

    /**
     * 获取用户聊天列表
     *
     * @param ChatLogic $chatLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatList(ChatLogic $chatLogic){
        $rows = $chatLogic->getUserChatList($this->uid());
        return $this->ajaxSuccess('success',$rows);
    }
}