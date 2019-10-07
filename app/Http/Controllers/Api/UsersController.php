<?php
namespace App\Http\Controllers\Api;

use App\Logic\UsersLogic;

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
}