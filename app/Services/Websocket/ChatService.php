<?php
namespace App\Services\Websocket;

use App\Models\UsersGroupMember;
use App\Models\UsersChatRecords;
use App\Models\UsersChatList;
use App\Helpers\Cache\CacheHelper;
use App\Models\UsersFriends;
use App\Models\UsersGroup;

class ChatService
{

    /**
     * 判断发送者和接受者是否是好友关系
     *
     * @param int $send_user_id 发送者用户ID
     * @param int $receive_user_id 接收者用户ID
     * @return bool|mixed
     */
    public static function checkFriends(int $send_user_id,int $receive_user_id){
        $isTrue = CacheHelper::getFriendRelationCache($send_user_id,$receive_user_id);
        if($isTrue === null){
            $isTrue = UsersFriends::checkFriends($send_user_id,$receive_user_id);
            CacheHelper::setFriendRelationCache($send_user_id,$receive_user_id,$isTrue?1:0);
            return $isTrue;
        }else{
            return boolval($isTrue);
        }
    }

    /**
     * 判断发送者与群ID是否存在群成员关系
     *
     * @param int $send_user_id 发送者用户ID
     * @param int $group_id 聊天群ID
     * @return bool|mixed
     */
    public static function checkGroupMember(int $send_user_id,int $group_id){
        $isTrue = CacheHelper::getGroupRelationCache($send_user_id,$group_id);
        if($isTrue === null){
            $isTrue = UsersGroup::checkGroupMember($send_user_id,$group_id);
            CacheHelper::setGroupRelationCache($send_user_id,$group_id,$isTrue?1:0);
            return $isTrue;
        }else{
            return boolval($isTrue);
        }
    }


    /**
     * 获取群聊成员的用户信息
     *
     * @param int $group_id 聊天ID
     * @param int $user_id 用户ID
     * @return array|mixed
     */
    public static function getUsersGroupMemberInfo(int $group_id,int $user_id){
        $info = CacheHelper::getUserGroupVisitCard($group_id, $user_id);
        if (!$info) {
            $res = UsersGroupMember::from('users_group_member as ugm')
                ->select(['users.nickname', 'users.avatarurl', 'ugm.visit_card'])
                ->leftJoin('users', 'users.id', '=', 'ugm.user_id')
                ->where('ugm.group_id', $group_id)->where('ugm.user_id', $user_id)
                ->first();

            $info = [
                'avatar'=>$res->avatarurl,
                'nickname'=>$res->nickname,
                'visit_card'=>$res->visit_card
            ];

            CacheHelper::setUserGroupVisitCard($group_id, $user_id, $info);
        }

        return $info;
    }

    /**
     * 保存用户聊天记录
     * @param array $message 聊天数据
     * @return bool
     */
    public static function saveChatRecord(array $message)
    {
        $recordRes = UsersChatRecords::create([
            'source' => $message['source_type'],
            'msg_type' => $message['msg_type'],
            'user_id' => $message['send_user'],
            'receive_id' => $message['receive_user'],
            'text_msg' => $message['content'],
            'send_time' => $message['send_time'],
        ]);

        if (!$recordRes) {
            return false;
        }

        //判断聊天消息类型
        if ($message['source_type'] == 1) {
            //创建好友的聊天列表
            if ($info = UsersChatList::select('id', 'status')->where('type', 1)->where('uid', $message['receive_user'])->where('friend_id', $message['send_user'])->first()) {
                if ($info->status == 0) {
                    UsersChatList::where('id', $info->id)->update(['status' => 1]);
                }
            } else {
                UsersChatList::create(['type' => 1, 'uid' => $message['receive_user'], 'friend_id' => $message['send_user'], 'status' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }

            //设置未读消息
            CacheHelper::setChatUnreadNum($message['receive_user'], $message['send_user']);
        } else if ($message['source_type'] == 2) {//群聊
            if ($uids = UsersGroupMember::where('group_id', $message['receive_user'])->where('status', 0)->pluck('user_id')->toArray()) {
                UsersChatList::where('group_id', $message['receive_user'])->whereIn('uid', $uids)->where('status', 0)->update(['status' => 1]);
            }
        }

        //缓存最后一条聊天记录
        CacheHelper::setLastChatCache($message['content'], $message['receive_user'], $message['source_type'] == 1 ? $message['send_user'] : 0);
        return true;
    }
}
