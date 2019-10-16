<?php
namespace App\Logic;

use App\Models\UsersChatList;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UsersChatRecords;

class ChatLogic extends Logic
{

    /**
     * 获取用户的聊天列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserChatList(int $user_id){
        $res1 = UsersChatList::select(['users_chat_list.type','users_chat_list.friend_id','users_chat_list.group_id','users_chat_list.created_at','users.nickname','users.avatarurl'])
            ->leftJoin('users','users_chat_list.friend_id','=','users.id')
            ->where('users_chat_list.uid',$user_id)->where('users_chat_list.type',1)->where('users_chat_list.status',1)->get()->toArray();

        $res12 = UsersChatList::select(['users_chat_list.type','users_chat_list.friend_id','users_chat_list.group_id','users_chat_list.created_at',DB::raw('lar_users_group.group_name as nickname, "" as avatarurl')])
            ->leftJoin('users_group','users_chat_list.group_id','=','users_group.id')
            ->where('users_chat_list.uid',$user_id)->where('users_chat_list.type',2)->where('users_chat_list.status',1)->get()->toArray();

        return array_merge($res1,$res12);
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
}
