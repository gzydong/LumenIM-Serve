<?php
namespace App\Logic;

use App\Models\User;
use App\Models\UsersFriends;
use App\Models\UsersGroupMember;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            $data['nickname']    = Str::random(10);
            $data['password']    = Hash::make($data['password']);
            $data['created_at']  = date('Y-m-d H:i:s');
            $isTrue = User::create($data);
        }catch (\Exception $e){
            $isTrue = false;
        }

        return $isTrue;
    }

    /**
     * 获取用户好友列表
     *
     * @param int $uid 用户ID
     * @return mixed
     */
    public function getUserFriends(int $uid){
        return (new UsersFriends())->getUserFriends($uid);
    }

    /**
     * 获取用户所在的群聊
     *
     * @param int $uid 用户ID
     */
    public function getUserChatGroups(int $uid){

    }

    /**
     * 获取用户所有的群聊ID
     *
     * @param int $user_id
     * @return mixed
     */
    public static function getUserGroupIds(int $user_id){
        return UsersGroupMember::where('uid',$user_id)->get()->pluck('group_id')->toarray();
    }
}
