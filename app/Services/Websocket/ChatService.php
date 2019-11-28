<?php

namespace App\Services\Websocket;

use App\Models\UsersGroupMember;
use App\Models\UsersChatRecords;
use App\Models\UsersChatList;
use App\Helpers\Cache\CacheHelper;

class ChatService
{


    /**
     * 保存聊天记录
     *
     * @param array $receive_msg 聊天数据
     * @return bool
     */
    public static function saveChatRecord(array $receive_msg)
    {
        $recordRes = UsersChatRecords::create([
            'source' => $receive_msg['sourceType'],
            'msg_type' => $receive_msg['msgType'],
            'user_id' => $receive_msg['sendUser'],
            'receive_id' => $receive_msg['receiveUser'],
            'text_msg' => $receive_msg['textMessage'],
            'send_time' => $receive_msg['send_time'],
        ]);

        if (!$recordRes) {
            return false;
        }

        //判断聊天消息类型
        if ($receive_msg['sourceType'] == 1) {
            //创建好友的聊天列表
            if ($info = UsersChatList::select('id', 'status')->where('type', 1)->where('uid', $receive_msg['receiveUser'])->where('friend_id', $receive_msg['sendUser'])->first()) {
                if ($info->status == 0) {
                    UsersChatList::where('id', $info->id)->update(['status' => 1]);
                }
            } else {
                UsersChatList::create(['type' => 1, 'uid' => $receive_msg['receiveUser'], 'friend_id' => $receive_msg['sendUser'], 'status' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }

            //设置未读消息
            CacheHelper::setChatUnreadNum($receive_msg['receiveUser'], $receive_msg['sendUser']);
        } else if ($receive_msg['sourceType'] == 2) {//群聊
            if ($uids = UsersGroupMember::where('group_id', $receive_msg['receiveUser'])->where('status', 0)->pluck('user_id')->toArray()) {
                UsersChatList::where('group_id', $receive_msg['receiveUser'])->whereIn('uid', $uids)->where('status', 0)->update(['status' => 1]);
            }
        }

        //缓存最后一条聊天记录
        CacheHelper::setLastChatCache($receive_msg['textMessage'], $receive_msg['receiveUser'], $receive_msg['sourceType'] == 1 ? $receive_msg['sendUser'] : 0);
        return true;
    }


    public static function getUsersGroupMemberInfo(int $group_id,int $user_id){
        $info = CacheHelper::getUserGroupVisitCard($group_id, $user_id);
        if (!$info) {
            $res = UsersGroupMember::from('users_group_member as ugm')
                ->select(['users.nickname', 'users.avatarurl', 'ugm.visit_card'])
                ->leftJoin('users', 'users.id', '=', 'ugm.user_id')
                ->where('ugm.group_id', $group_id)->where('ugm.user_id', $user_id)
                ->first();

            $info = [];
            $info['avatar'] = $res->avatarurl;
            $info['nickname'] = $res->nickname;
            $info['visit_card'] = $res->visit_card;

            CacheHelper::setUserGroupVisitCard($msgData['receiveUser'], $msgData['sendUser'], $info);
        }

        return $info;
    }
}
