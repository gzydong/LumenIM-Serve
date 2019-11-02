<?php
namespace App\Logic;

use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersChatRecords;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ChatLogic extends Logic
{

    /**
     * 获取用户的聊天列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserChatList(int $user_id){
        $rows = UsersChatList::select(['users_chat_list.id','users_chat_list.type','users_chat_list.friend_id','users_chat_list.group_id','users_chat_list.created_at'])
            ->where('users_chat_list.uid',$user_id)
            ->where('users_chat_list.status',1)->orderBy('id','desc')->get()->toArray();


        if(empty($rows)){
            return [];
        }

        $friend_ids = $group_ids = [];
        foreach ($rows as $key=>$v){
            $rows[$key]['name'] = '';//对方昵称/群名称
            $rows[$key]['unread_num'] = 0;//未读消息数量
            $rows[$key]['msg_text'] = '......';//最新一条消息内容
            $rows[$key]['avatar'] = 'https://ss0.bdstatic.com/70cFuHSh_Q1YnxGkpoWK1HF6hhy/it/u=3987166397,2421475227&fm=26&gp=0.jpg';//默认头像

            if($v['type'] == 1){
                $friend_ids[] = $v['friend_id'];
            }else{
                $group_ids[] = $v['group_id'];
            }
        }

        $friendInfos = $groupInfos = [];
        if($group_ids){
            $groupInfos = UsersGroup::whereIn('id',$group_ids)->get(['id','group_name','people_num','avatarurl'])->toArray();
            $groupInfos = replaceArrayKey('id',$groupInfos);
        }


        if($friend_ids){
            $friendInfos = User::whereIn('id',$friend_ids)->get(['id','nickname','avatarurl'])->toArray();
            $friendInfos = replaceArrayKey('id',$friendInfos);
        }

        foreach ($rows as $key2=>$v2){
            if($v2['type'] == 1){

                $rows[$key2]['avatar'] = $friendInfos[$v2['friend_id']]['avatarurl'];
                $rows[$key2]['name'] = $friendInfos[$v2['friend_id']]['nickname'];

                $info = UsersFriends::select('user1','user2','user1_remark','user2_remark')->where('user1',($user_id < $v2['friend_id'])? $user_id:$v2['friend_id'])->where('user2',($user_id < $v2['friend_id'])? $v2['friend_id'] : $user_id)->first();
                //这个环节待优化
                if($info){
                    if($info->user1 == $v2['friend_id'] && !empty($info->user2_remark)){
                        $rows[$key2]['name'] = $info->user2_remark;
                    }else if($info->user2 == $v2['friend_id'] && !empty($info->user1_remark)){
                        $rows[$key2]['name'] = $info->user1_remark;
                    }
                }

                $flagKey = $user_id < $v2['friend_id'] ? "{$user_id}_{$v2['friend_id']}" : "{$v2['friend_id']}_{$user_id}";
                $rows[$key2]['msg_text'] = Redis::hget('friends.chat.last.msg',$flagKey) ? : $v2['msg_text'];
            }else{
                $rows[$key2]['avatar'] = $groupInfos[$v2['group_id']]['avatarurl'];
                $rows[$key2]['name'] = $groupInfos[$v2['group_id']]['group_name'];
                $rows[$key2]['msg_text'] = Redis::hget('groups.chat.last.msg',$v2['group_id']) ? : $v2['msg_text'];
            }
        }

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
            return ['rows'=>[],'record_id'=>0];
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
                $item->avatar = $infos[0]['avatarurl'];
            }else{
                $item->avatar = $infos[1]['avatarurl'];
            }

            $item->nickname = '';
            $item->nickname_remarks = '';
            return (array)$item;
        },DB::select($sql));

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
            SELECT users.id,users.avatarurl as avatar,users.nickname,tmp_table.nickname_remarks from lar_users users
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
    public function launchGroupChat(int $user_id,string $group_name,string $group_profile,$uids = []){
        array_unshift($uids,$user_id);
        $groupMember = [];

        DB::beginTransaction();
        try{
            $insRes = UsersGroup::create(['user_id'=>$user_id,'group_name'=>$group_name,'group_profile'=>$group_profile,'people_num'=>count($uids),'status'=>0,'created_at'=>date('Y-m-d H:i:s')]);
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

        return [true,['group_info'=>$insRes->toArray(),'uids'=>$uids]];
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
    public function removeGroupChat(int $group_id,int $group_owner_id,int $group_member_id,$group_owner = false){
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

    /**
     * 创建聊天列表记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID
     * @param int $type 创建类型 1:私聊  2:群聊
     * @return int
     */
    public function createChatList(int $user_id,int $receive_id,int $type){
        $result = UsersChatList::where('uid',$user_id)->where('type',$type)->where($type == 1 ? 'friend_id' : 'group_id',$receive_id)->first();
        if($result){
            if($result->status == 0){
                $result->status = 1;
                $result->save();
            }
        }else{
            $data = [
                'type'=>$type,
                'uid'=>$user_id,
                'status'=>1,
                'friend_id'=>$type == 1 ? $receive_id :0,
                'group_id'=>$type == 2 ? $receive_id :0,
                'created_at'=>date('Y-m-d H:i:s')
            ];

            if(!$result = UsersChatList::create($data)){
                return 0;
            }
        }

        return $result->id;
    }

    /**
     * 获取聊天群
     *
     * @param int $user_id 用户ID
     * @param int $group_id 聊天群ID
     * @return array
     */
    public function getGroupDetail(int $user_id,int $group_id){
        $groupInfo = UsersGroup::select(['id','user_id','group_name','people_num','avatarurl','created_at'])->where('id',$group_id)->where('status',0)->first();
        if(!$groupInfo){
            return [];
        }

        $members = UsersGroupMember::select([
            'users_group_member.id','users_group_member.group_owner','users_group_member.user_id','users.avatarurl','users.nickname',
        ])
        ->leftJoin('users','users.id','=','users_group_member.user_id')
        ->where([
            ['users_group_member.group_id', '=', $group_id],
            ['users_group_member.status', '=', 0],
        ])->get()->toArray();

        return [
            'group_id'=>$group_id,
            'group_owner'=>User::where('id',$groupInfo->user_id)->value('nickname'),
            'group_name'=>$groupInfo->group_name,
            'people_num'=>$groupInfo->people_num,
            'group_avatar'=>$groupInfo->avatarurl,
            'created_at'=>$groupInfo->created_at,
            'members'=>$members
        ];
    }
}
