<?php

namespace App\Services\Websocket;

use App\Facades\WebSocketHelper;
use App\Models\User;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Models\UsersChatRecords;
use App\Models\UsersChatList;
use App\Models\UsersGroupMember;
use SwooleTW\Http\Websocket\Facades\Websocket;
use App\Helpers\Cache\CacheHelper;

class ChatService
{

    /**
     * 验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
     *
     * @param array $receive_msg 接受的消息
     * @return bool
     */
    public function check(array $receive_msg)
    {
        //判断用户是否存在
        $receive = WebSocketHelper::getUserFds($receive_msg['sendUser']);
        if (!User::checkUserExist($receive_msg['sendUser'])) {
            $receive_msg['textMessage'] = '非法操作！';
            Websocket::to($receive)->emit('notify', $receive_msg);
            return false;
        }

        if ($receive_msg['sourceType'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!UsersFriends::checkFriends($receive_msg['sendUser'], $receive_msg['receiveUser'])) {
                $receive_msg['textMessage'] = '温馨提示:您当前与对方尚未成功好友！';
                Websocket::to($receive)->emit('notify', $receive_msg);
                return false;
            }
        } else if ($receive_msg['sourceType'] == 2) {//群聊
            //判断是否属于群成员
            if (!UsersGroup::checkGroupMember($receive_msg['receiveUser'], $receive_msg['sendUser'])) {
                $receive_msg['textMessage'] = '温馨提示:您还没有加入该聊天群';
                Websocket::to($receive)->emit('notify', $receive_msg);
                return false;
            }
        }

        return true;
    }

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
}
