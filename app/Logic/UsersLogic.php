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

    /**
     * 通过手机号查找用户
     *
     * @param string $mobile
     * @param int $user_id 当前登录用户的ID
     * @return array
     */
    public function searchUserInfo(string $mobile,int $user_id){
        $info = User::select(['id','mobile','nickname','avatarurl','gender'])->where('mobile',$mobile)->first();
        $info = $info ? $info->toArray() : [];
        if($info){
            $info['friend_status'] = 0;//朋友关系状态  0:本人  1:陌生人 2:朋友
            $info['nickname_remark'] = '';
            if($info['id'] != $user_id){
                $friend_id = $info['id'];
                $friendInfo = UsersFriends::select('id','user1','user2','active','user1_remark','user2_remark')->where(function ($query) use ($friend_id,$user_id) {
                    $query->where('user1', '=', $user_id)->where('user2', '=', $friend_id)->where('status',1);
                })->orWhere(function ($query) use ($friend_id,$user_id) {
                    $query->where('user1', '=', $friend_id)->where('user2', '=', $user_id)->where('status',1);
                })->first();

                $info['friend_status'] = $friendInfo ? 2 : 1;
                $info['nickname_remark'] = ($friendInfo->user1 == $friend_id) ? $friendInfo->user2_remark : $friendInfo->user1_remark;
            }
        }

        return $info;
    }
}
