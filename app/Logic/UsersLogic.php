<?php
namespace App\Logic;

use App\Models\User;
use App\Models\UsersFriends;
use App\Models\UsersFriendsApply;
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
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserFriends(int $user_id){
        return UsersFriends::getUserFriends($user_id);
    }

    /**
     * 获取用户所在的群聊
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserChatGroups(int $user_id){
        return UsersGroupMember::select(['users_group.id','users_group.group_name','users_group.avatarurl','users_group.group_profile','users_group_member.not_disturb'])
            ->join('users_group','users_group.id','=','users_group_member.group_id')
            ->where('users_group_member.user_id',$user_id)->where('users_group_member.status',0)->orderBy('id','desc')->get()->toarray();
    }

    /**
     * 获取用户所有的群聊ID
     *
     * @param int $user_id
     * @return mixed
     */
    public static function getUserGroupIds(int $user_id){
        return UsersGroupMember::where('user_id',$user_id)->where('status',0)->get()->pluck('group_id')->toarray();
    }

    /**
     * 通过手机号查找用户
     *
     * @param array $where 查询条件
     * @param int $user_id 当前登录用户的ID
     * @return array
     */
    public function searchUserInfo(array $where,int $user_id){
        $info = User::select(['id','mobile','nickname','avatarurl','gender','motto']);
        if(isset($where['uid'])){
            $info->where('id',$where['uid']);
        }

        if(isset($where['mobile'])){
            $info->where('mobile',$where['mobile']);
        }

        $info = $info->first();
        $info = $info ? $info->toArray() : [];
        if($info){
            $info['friend_status'] = 0;//朋友关系状态  0:本人  1:陌生人 2:朋友
            $info['nickname_remark'] = '';
            $info['friend_apply'] = 0;
            if($info['id'] != $user_id){
                $friend_id = $info['id'];
                $friendInfo = UsersFriends::select('id','user1','user2','active','user1_remark','user2_remark')->where(function ($query) use ($friend_id,$user_id) {
                    $query->where('user1', '=', $user_id)->where('user2', '=', $friend_id)->where('status',1);
                })->orWhere(function ($query) use ($friend_id,$user_id) {
                    $query->where('user1', '=', $friend_id)->where('user2', '=', $user_id)->where('status',1);
                })->first();

                $info['friend_status'] = $friendInfo ? 2 : 1;
                if($friendInfo){
                    $info['nickname_remark'] = ($friendInfo->user1 == $friend_id) ? $friendInfo->user2_remark : $friendInfo->user1_remark;
                }else{
                    $res = UsersFriendsApply::where('user_id',$user_id)->where('friend_id',$info['id'])->where('status',0)->orderBy('id','desc')->exists();
                    $info['friend_apply'] = $res ? 1 : 0;
                }
            }
        }

        return $info;
    }
}
