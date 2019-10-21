<?php
namespace App\Logic;

use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersChatRecords;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;
use Illuminate\Support\Facades\DB;

class ChatLogic extends Logic
{

    /**
     * 获取用户的聊天列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserChatList(int $user_id){
        $rows = UsersChatList::select(['users_chat_list.type','users_chat_list.friend_id','users_chat_list.group_id','users_chat_list.created_at','users.nickname','users.avatarurl'])
            ->leftJoin('users','users_chat_list.friend_id','=','users.id')
            ->where(function ($query) use($user_id){
                $query->where('users_chat_list.uid',$user_id)->orWhere('users_chat_list.friend_id',$user_id);
            })
            ->where('users_chat_list.type',1)
            ->where('users_chat_list.status',1)->get()->toArray();

        $rows = array_map(function ($item){
            $item['num'] = 0;
            $item['text'] = '';
            return $item;
        },$rows);

        return $rows;
    }

    /**
     * 获取私信聊天记录
     *
     * @param int $record_id 记录ID
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID
     * @param int $page_size 分页大小
     * @return array
     */
    public function getPrivateChatInfos(int $record_id,int $user_id,int $receive_id,$page_size = 20){
        $infos = User::select('id','avatarurl')->find([$user_id,$receive_id])->toArray();
        if($infos && count($infos) != 2){
            return [];
        }

        $whereID = ($record_id == 0) ? '' : " and `id` < {$record_id}";
        $sql = <<<SQL
                    select * from (
                        select * from `lar_users_chat_records` where  `receive_id` = {$user_id} and `user_id` = {$receive_id} and `source` = 1 {$whereID}
                          UNION
                        select * from `lar_users_chat_records` where  `receive_id` = {$receive_id} and `user_id` = {$user_id} and `source` = 1 {$whereID}
                    ) tmp_table ORDER BY id desc  limit {$page_size}
SQL;

        $rows = array_map(function ($item) use($infos){
            if($infos[0]['id'] == $item->user_id){
                $item->avatarurl = $infos[0]['avatarurl'];
            }else{
                $item->avatarurl = $infos[1]['avatarurl'];
            }

            $item->nickname = '';
            $item->nickname_remarks = '';
            return (array)$item;
        },DB::select($sql));

        unset($infos);
        return ['rows'=>$rows,'record_id'=>end($rows)['id']];
    }

    /**
     * 获取群聊的聊天记录
     *
     * @param int $record_id 记录ID
     * @param int $receive_id 群聊ID
     * @param int $user_id 用户ID
     * @param int $page_size 分页大小
     * @return array
     */
    public function getGroupChatInfos(int $record_id,int $receive_id,int $user_id,$page_size = 20){
        $sqlObj = UsersChatRecords::where('receive_id',$receive_id)->where('source',2);
        if($record_id > 0){
            $sqlObj->where('id','<',$record_id);
        }

        $rows = $sqlObj->orderBy('id','desc')->limit($page_size)->get()->toArray();
        if($rows){

            $uids = implode(',',array_unique(array_column($rows,'user_id')));

            $sql = <<<SQL
            SELECT users.id,users.avatarurl,users.nickname,tmp_table.nickname_remarks from lar_users users
            left JOIN (
            SELECT user2 as friend_id,user1_remark as nickname_remarks  from lar_users_friends where user1 = {$user_id} and user2 in ({$uids}) 
              UNION 
            SELECT user1 as friend_id,user2_remark as nickname_remarks from lar_users_friends where user2 = {$user_id} and user1 in ({$uids})
            ) tmp_table on users.id = tmp_table.friend_id where users.id in ({$uids})
SQL;

            $userInfos = array_map(function ($item){return (array)$item;},DB::select($sql));
            $userInfos = replaceArrayKey('id',$userInfos);

            $rows = array_map(function ($val) use ($userInfos) {
                unset($userInfos[$val['user_id']]['id']);
                return array_merge($val,$userInfos[$val['user_id']]);
            },$rows);

            unset($userInfos);
        }

        return ['rows'=>$rows,'record_id'=>end($rows)['id']];
    }

    /**
     * 创建群聊
     *
     * @param int $user_id 用户ID
     * @param string $group_name 群聊名称
     * @param array $uids 群聊用户ID(不包括群成员)
     * @return array
     */
    public function launchGroupChat(int $user_id,string $group_name,$uids = []){
        array_unshift($uids,$user_id);
        $groupMember = [];

        DB::beginTransaction();
        try{
            $insRes = UsersGroup::create(['user_id'=>$user_id,'group_name'=>$group_name,'people_num'=>count($uids),'status'=>0,'created_at'=>date('Y-m-d H:i:s')]);
            if(!$insRes){
                throw new \Exception('创建群失败');
            }

            foreach ($uids as $k=>$uid){
                $groupMember[] = [
                    'group_id'=>$insRes->id,
                    'user_id'=>$uid,
                    'group_owner'=>($k == 0) ? 1 : 0,
                    'status'=>0,
                    'created_at'=>date('Y-m-d H:i:s'),
                ];
            }

            if(!DB::table('users_group_member')->insert($groupMember)){
                throw new \Exception('创建群成员信息失败');
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return [false,[]];
        }

        unset($groupMember);
        unset($insRes);
        return [true,$uids];
    }

    /**
     * 邀请好友加入群聊
     *
     * @param int $group_id 群ID
     * @param int $friends_id 好友ID
     * @return bool
     */
    public function inviteFriendsGroupChat(int $group_id,int $friends_id){
        $info = UsersGroupMember::select(['id','status'])->where('group_id')->where('user_id',$friends_id)->first();
        if($info && $info->status == 0){
            return false;
        }

        try{
            if($info){
                $info->status = 1;$info->save();
            }else{
                if(!UsersGroupMember::create(['group_id'=>$group_id,'user_id'=>$friends_id,'group_owner'=>0,'status'=>0,'created_at'=>date('Y-m-d H:i:s')])){
                    throw new \Exception('创建群成员信息失败');
                }
            }

            UsersGroup::where('group_id',$group_id)->increment('people_num');
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return false;
        }
        return true;
    }

    /**
     * 将指定的用户踢出群聊
     *
     * @param int $group_id 群ID
     * @param int $group_owner_id 操作用户ID
     * @param int $group_member_id  群成员ID
     * @return bool
     */
    public function removeGroupChat(int $group_id,int $group_owner_id,int $group_member_id){
        if(!UsersGroup::where('id',$group_id)->where('user_id',$group_owner_id)->exists()){
            return false;
        }

        DB::beginTransaction();
        try{
            if(!UsersGroupMember::where('group_id',$group_id)->where('user_id',$group_member_id)->where('group_owner',0)->update(['status'=>0])){
                throw new \Exception('修改群成员状态失败');
            }

            UsersGroup::where('group_id',$group_id)->decrement('people_num');
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 解散指定的群聊
     *
     * @param int $group_id 群ID
     * @param int $user_id  用户ID
     * @return bool
     */
    public function dismissGroupChat(int $group_id,int $user_id){
        if(!UsersGroup::where('id',$group_id)->where('status',0)->exists()){
            return false;
        }

        //判断执行者是否属于群主
        if(!UsersGroupMember::where('group_id',$group_id)->where('user_id',$user_id)->where('group_owner',1)->exists()){
            return false;
        }

        DB::beginTransaction();
        try{
            UsersGroup::where('id',$group_id)->update(['status'=>1]);
            UsersGroupMember::where('group_id',$group_id)->update(['status'=>1]);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return false;
        }

        return true;
    }
}
