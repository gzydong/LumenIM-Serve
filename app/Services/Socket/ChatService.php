<?php

namespace App\Services\Socket;

use App\Models\UsersChatFiles;
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
    public static function checkFriends(int $send_user_id, int $receive_user_id)
    {
        $isTrue = CacheHelper::getFriendRelationCache($send_user_id, $receive_user_id);
        if ($isTrue === null) {
            $isTrue = UsersFriends::checkFriends($send_user_id, $receive_user_id);
            CacheHelper::setFriendRelationCache($send_user_id, $receive_user_id, $isTrue ? 1 : 0);
            return $isTrue;
        } else {
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
    public static function checkGroupMember(int $send_user_id, int $group_id)
    {
        $isTrue = CacheHelper::getGroupRelationCache($send_user_id, $group_id);
        if ($isTrue === null) {
            $isTrue = UsersGroup::checkGroupMember($send_user_id, $group_id);
            CacheHelper::setGroupRelationCache($send_user_id, $group_id, $isTrue ? 1 : 0);
            return $isTrue;
        } else {
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
    public static function getUsersGroupMemberInfo(int $group_id, int $user_id)
    {
        $info = CacheHelper::getUserGroupVisitCard($group_id, $user_id);
        if (!$info) {
            $res = UsersGroupMember::from('users_group_member as ugm')
                ->select(['users.nickname', 'users.avatarurl', 'ugm.visit_card'])
                ->leftJoin('users', 'users.id', '=', 'ugm.user_id')
                ->where('ugm.group_id', $group_id)->where('ugm.user_id', $user_id)
                ->first();

            $info = [
                'avatar' => $res->avatarurl,
                'nickname' => $res->nickname,
                'visit_card' => $res->visit_card
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
        if (!$recordRes = UsersChatRecords::create($message)) {
            return false;
        }

        //判断聊天消息类型
        if ($message['source'] == 1) {
            //创建好友的聊天列表
            if ($info = UsersChatList::select('id', 'status')->where('type', 1)->where('uid', $message['receive_id'])->where('friend_id', $message['user_id'])->first()) {
                if ($info->status == 0) {
                    UsersChatList::where('id', $info->id)->update(['status' => 1]);
                }
            } else {
                UsersChatList::create(['type' => 1, 'uid' => $message['receive_id'], 'friend_id' => $message['user_id'], 'status' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }

            //设置未读消息
            CacheHelper::setChatUnreadNum($message['receive_id'], $message['user_id']);
        } else if ($message['source'] == 2) {//群聊
            if ($uids = UsersGroupMember::where('group_id', $message['receive_id'])->where('status', 0)->pluck('user_id')->toArray()) {
                UsersChatList::where('group_id', $message['receive_id'])->whereIn('uid', $uids)->where('status', 0)->update(['status' => 1]);
            }
        }

        //缓存最后一条聊天记录
        $text = $message['content'];
        if ($message['msg_type'] == 2) {
            $type = UsersChatFiles::where('id', $message['file_id'])->value('file_type');
            $text = $type == '1' ? '[图片消息]' : '[文件消息]';
        } else if ($message['msg_type'] == 3) {
            $text = '[系统提示:好友入群消息]';
        } else if ($message['msg_type'] == 4) {
            $text = '[系统提示:好友退群消息]';
        }

        CacheHelper::setLastChatCache(['send_time' => $message['send_time'], 'text' => $text], $message['receive_id'], $message['source'] == 1 ? $message['user_id'] : 0);
        return $recordRes->id;
    }
}