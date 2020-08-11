<?php
namespace App\Logic;

use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersFriends;
use App\Models\UsersFriendsApply;
use App\Models\UsersGroupMember;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 用户逻辑处理层
 * Class UsersLogic
 * @package App\Logic
 */
class UsersLogic extends Logic
{
    /**
     * 验证用户密码是否正确
     *
     * @param string $str 用户输入密码
     * @param string $password 账户密码
     * @return bool
     */
    public function checkAccountPassword(string $str,string $password){
        return Hash::check($str, $password);
    }

    /**
     * 获取用户信息
     *
     * @param int $user_id 用户ID
     * @param array $filed 查询字段
     * @return mixed
     */
    public function getUserInfo(int $user_id,$filed = ['*']){
        return User::where('id',$user_id)->first($filed);
    }

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
     * 账号重置密码
     *
     * @param string $mobile 用户手机好
     * @param string $password 新密码
     * @return mixed
     */
    public function resetPassword(string $mobile,string $password){
        return User::where('mobile',$mobile)->update(['password'=>Hash::make($password)]);
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
        $items = UsersGroupMember::select(['users_group.id','users_group.group_name','users_group.avatar','users_group.group_profile','users_group.user_id as group_user_id'])
            ->join('users_group','users_group.id','=','users_group_member.group_id')
            ->where('users_group_member.user_id',$user_id)->where('users_group_member.status',0)->orderBy('id','desc')->get()->toarray();

        if($items){
            foreach ($items as $key=>$item){
                $items[$key]['isGroupLeader'] = $item['group_user_id'] == $user_id;
                unset($items[$key]['group_user_id']);
                $items[$key]['not_disturb'] = UsersChatList::where('uid',$user_id)->where('type',2)->where('group_id',$item['id'])->value('not_disturb');
            }
        }

        return $items;
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
        $info = User::select(['id','mobile','nickname','avatar','gender','motto']);
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

    /**
     * 用户修改密码接口
     *
     * @param int $user_id 用户ID
     * @param string $old_password 原始密码
     * @param string $new_password 新密码
     * @return array
     */
    public function userChagePassword(int $user_id,string $old_password,string $new_password){
        $info = User::select(['id','password'])->where('id',$user_id)->first();
        if(!$info){
            return [false,'用户不存在'];
        }

        if(!Hash::check($old_password,$info->password)){
            return [false,'旧密码验证失败'];
        }

        if(!User::where('id',$user_id)->update(['password'=>Hash::make($new_password)])){
            return [false,'密码修改失败'];
        }

        return [true,'密码修改成功'];
    }

    /**
     * 换绑手机号
     *
     * @param int $user_id 用户ID
     * @param string $mobile 换绑手机号
     * @return array|bool
     */
    public function renewalUserMobile(int $user_id,string $mobile){
        $uid = User::where('mobile',$mobile)->value('id');
        if($uid)  return [false,'手机号已被他人绑定'];

        $isTrue = (bool)User::where('id',$user_id)->update(['mobile'=>$mobile]);
        return [$isTrue,null];
    }

    /**
     * 发送邮箱验证吗
     *
     * @param string $email
     * @return bool
     */
    public function sendEmailCode(string $email){
        $key = "email_code:{$email}";
        $sms_code = random(6, 'number');
        $res = Redis::setex($key, 60*15, $sms_code);
        if($res){
            $title = '绑定邮箱';
            Mail::send('emails.email-code', ['service_name' => $title, 'sms_code' => $sms_code, 'domain' => 'http://47.105.180.123:83'], function ($message) use ($email,$title) {
                $message->to($email)->subject("Lumen Im {$title}(验证码)");
            });
        }

        return true;
    }
}
