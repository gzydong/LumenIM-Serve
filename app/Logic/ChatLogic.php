<?php
namespace App\Logic;

use App\Models\UsersChatList;
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
        $res1 = UsersChatList::select(['users_chat_list.type','users_chat_list.friend_id','users_chat_list.group_id','users_chat_list.created_at','users.nickname','users.avatarurl'])
            ->leftJoin('users','users_chat_list.friend_id','=','users.id')
            ->where('users_chat_list.uid',$user_id)->where('users_chat_list.type',1)->where('users_chat_list.status',1)->get()->toArray();

        $res12 = UsersChatList::select(['users_chat_list.type','users_chat_list.friend_id','users_chat_list.group_id','users_chat_list.created_at',DB::raw('lar_users_group.group_name as nickname, "" as avatarurl')])
            ->leftJoin('users_group','users_chat_list.group_id','=','users_group.id')
            ->where('users_chat_list.uid',$user_id)->where('users_chat_list.type',2)->where('users_chat_list.status',1)->get()->toArray();

        return array_merge($res1,$res12);
    }
}