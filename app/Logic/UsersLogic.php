<?php

namespace App\Logic;

use App\User;

use Illuminate\Support\Facades\Hash;


/**
 * 用户逻辑处理层
 * Class UsersLogic
 * @package App\Logic
 */
class UsersLogic extends Logic
{

    /**
     * 账号注册逻辑
     * @param array $data
     * @return bool
     */
    public function register(array $data){
        try{
            $data['password']    = Hash::make($data['password']);
            $data['created_at']  = date('Y-m-d H:i:s');
            $isTrue = User::create($data);
        }catch (\Exception $e){
            $isTrue = false;
        }

        return $isTrue;
    }

    /**
     * 获取用户所有好友
     *
     * @param int $uid  用户ID
     */
    public function getUserFriends(int $uid){

    }

    /**
     * 获取用户所在的群聊
     *
     * @param int $uid 用户ID
     */
    public function getUserChatGroups(int $uid){

    }
}