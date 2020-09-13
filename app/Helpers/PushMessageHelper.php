<?php

namespace App\Helpers;

use App\Models\User;
use SwooleTW\Http\Websocket\Facades\Websocket;

/**
 * Socket 资源处理
 * Class PushMessageHelper
 * @package App\Helpers
 */
class PushMessageHelper
{
    // 消息事件类型
    const events = [
        'chat_message',//用户聊天消息
        'friend_apply',//好友添加申请消息
        'join_group',    //入群消息
        'login_notify',//好友登录消息通知
        'input_tip',//好友登录消息通知
        'revoke_records',//好友撤回消息通知
    ];

    /**
     * 推送 Socket 信息
     * @param string $event 消息事件类型
     * @param $receive 接受者
     * @param array $data 数据包
     * @return bool
     */
    public static function response(string $event, $receive, $data)
    {
        // 判断事件类型是否存在
        if (!in_array($event, self::events)) {
            return false;
        }

        if (in_array($event, ['login_notify', 'input_tip'])) {
            Websocket::broadcast()->to($receive)->emit($event, $data);
        } else if (!empty($receive)) {
            Websocket::to($receive)->emit($event, $data);
        } else {
            Websocket::emit($event, $data);
        }
    }

    /**
     * 格式化对话的消息体
     *
     * @param array $data 对话的消息
     * @return array
     */
    public static function formatTalkMsg(array $data)
    {
        // 缓存优化
        if (!isset($data['nickname']) || !isset($data['avatar']) || empty($data['nickname']) || empty($data['avatar'])) {
            if (isset($data['user_id']) && !empty($data['user_id'])) {
                $info = User::where('id', $data['user_id'])->first(['nickname', 'avatar']);
                if ($info) {
                    $data['nickname'] = $info->nickname;
                    $data['avatar'] = $info->avatar;
                }
            }
        }

        $arr = [
            "id" => 0,
            "source" => 1,
            "msg_type" => 1,
            "user_id" => 0,
            "receive_id" => 0,
            "content" => '',
            "is_revoke" => 0,

            // 发送消息人的信息
            "nickname" => "",
            "avatar" => "",

            // 不同的消息类型
            "file" => [],
            "code_block" => [],
            "forward" => [],
            "invite" => [],

            "created_at" => "",
        ];

        return array_merge($arr, array_intersect_key($data, $arr));
    }
}
