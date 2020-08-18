<?php

namespace App\Helpers\Socket;

use App\Helpers\Cache\CacheHelper;
use App\Models\User;
use App\Models\UsersChatFiles;
use App\Models\UsersGroupMember;
use App\Models\UsersChatRecords;
use App\Models\UsersChatList;
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
            $isTrue = UsersFriends::isFriend($send_user_id, $receive_user_id);
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
            $isTrue = UsersGroup::isMember($send_user_id, $group_id);
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
        $res = UsersGroupMember::from('users_group_member as ugm')
            ->select(['users.nickname', 'users.avatar', 'ugm.visit_card'])
            ->leftJoin('users', 'users.id', '=', 'ugm.user_id')
            ->where('ugm.group_id', $group_id)->where('ugm.user_id', $user_id)
            ->first();

        return [
            'avatar' => $res->avatar,
            'nickname' => $res->nickname,
            'visit_card' => $res->visit_card
        ];
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

        $text = str_replace(PHP_EOL, '', $text);
        CacheHelper::setLastChatCache(['send_time' => $message['send_time'], 'text' => substr($text, 0, 50)], $message['receive_id'], $message['source'] == 1 ? $message['user_id'] : 0);
        return $recordRes->id;
    }

    /**
     * 包装聊天对话数据
     *
     * @param int $send_user 发送消息的用户ID
     * @param int $receive_user 接受者消息ID(用户ID或群ID)
     * @param int $source_type 聊天类型  1:私聊     2:群聊
     * @param int $msg_type 消息类型 1:文本消息   2:文件消息
     * @param array $data 数据包
     * @return array
     */
    public static function getChatMessage(int $send_user, int $receive_user, int $source_type, int $msg_type, array $data)
    {
        $avatar = isset($data['avatar']) ? $data['avatar'] : User::where('id', $send_user)->value('avatar');

        $arr = [
            'id' => null,
            'user_id' => $send_user,
            'receive_id' => $receive_user,
            'msg_type' => 0,
            'source' => $source_type,
            'send_time' => date('Y-m-d H:i:s'),
            'float' => 'center',

            //发送者个人信息
            'avatar' => $avatar,
            'nickname' => '',
            'friend_remarks' => '',

            //消息是否已撤回
            'is_revoke' => 0,

            //文本消息
            'content' => '',

            //文件消息信息
            'file_id' => 0,
            'file_original_name' => '',
            'file_size' => '',
            'file_suffix' => '',
            'file_type' => '',
            'file_url' => '',
            'file_source' => '',

            //代码块消息
            'is_code' => 0,
            'code_lang' => '',

            //转发消息
            'forward_id' => 0,
            'forward_info' => [],

            //群通知(1:入群通知  2:退群通知)
            'group_notify' => [
                'type' => 1,
                'operate_user' => null,
                'users' => []
            ]
        ];

        return [
            'send_user' => $send_user,
            'receive_user' => $receive_user,
            'source_type' => $source_type,
            'msg_type' => $msg_type == 2 ? 2 : 1,
            'data' => array_merge($arr, array_intersect_key($data, $arr))
        ];
    }
}
