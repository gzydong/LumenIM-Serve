<?php
namespace App\Logic;

use App\Models\User;
use App\Models\UsersFriendsApply;
use App\Models\UsersFriends;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class FriendsLogic extends Logic
{

    /**
     * 创建好友的申请
     *
     * @param int $user_id  用户ID
     * @param int $friend_id  好友ID
     * @param string $remarks  好友申请备注
     * @return bool
     */
    public static function addFriendApply(int $user_id,int $friend_id,string $remarks){
        $res = UsersFriendsApply::create([
            'user_id'=>$user_id,
            'friend_id'=>$friend_id,
            'status'=>0,
            'remarks'=>$remarks,
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ]);

        return $res ? true : false;
    }

    /**
     * 处理好友的申请
     *
     * @param int $user_id  用户ID
     * @param int $apply_id 申请记录ID
     * @param int $status  申请状态(1:已同意  2:已拒绝)
     * @param string $remarks  备注信息(当$status =1 时代表好友昵称备注，$status =2时代表拒绝原因)
     * @return bool
     */
    public static function handleFriendApply(int $user_id,int $apply_id,int $status,$remarks = ''){
        $info = UsersFriendsApply::where('id',$apply_id)->where('friend_id',$user_id)->where('status',0)->first();
        if(!$info){
            return false;
        }

        if($status == 1){//同意添加好友
            //查询是否存在好友记录
            $isFriend = UsersFriends::select('id','user1','user2','active','status')->where(function ($query) use ($info) {
                $query->where('user1', '=', $info->user_id)->where('user2', '=', $info->friend_id);
            })->orWhere(function ($query) use ($info) {
                $query->where('user2', '=', $info->user_id)->where('user1', '=', $info->friend_id);
            })->first();

            DB::beginTransaction();
            try{
                $res = UsersFriendsApply::where('id',$apply_id)->update(['status'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
                if(!$res){
                    throw new Exception('更新好友申请表信息失败');
                }

                if($isFriend){
                    $active = ($isFriend->user1 == $info->user_id && $isFriend->user2 == $info->friend_id) ? 1 :2;
                    if(!UsersFriends::where('id',$isFriend->id)->update(['active'=>$active,'status'=>1])){
                        throw new Exception('更新好友关系信息失败');
                    }
                }else{
                    $insRes = UsersFriends::create([
                        'user1'=>$info->user_id,
                        'user2'=>$info->friend_id,
                        'user1_remark'=>User::where('id',$info->friend_id)->value('nickname'),
                        'user2_remark'=>$remarks,
                        'active'=>1,
                        'status'=>1,
                        'agree_time'=>date('Y-m-d H:i:s'),
                        'created_at'=>date('Y-m-d H:i:s'),
                    ]);
                    if(!$insRes){
                        throw new Exception('创建好友关系失败');
                    }
                }

                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                return false;
            }

            return true;
        }else if($status == 2) {//拒绝添加好友
            $res = UsersFriendsApply::where('id',$apply_id)->update(['status'=>2,'updated_at'=>date('Y-m-d H:i:s'),'reason'=>$remarks]);
            return $res ? true : false;
        }

        return false;
    }

    /**
     * 取消好友关系
     *
     * @param int $user_id  用户ID
     * @param int $friend_id  好友ID
     * @return bool
     */
    public static function removeFriend(int $user_id,int $friend_id){
        if(!UsersFriends::checkFriends($user_id,$friend_id)){
            return false;
        }

        $data = ['status'=>0];
        if(UsersFriends::where('user1',$user_id)->where('user2',$friend_id)->update($data) || UsersFriends::where('user2',$user_id)->where('user1',$friend_id)->update($data)){
            return true;
        }

        return false;
    }

}
