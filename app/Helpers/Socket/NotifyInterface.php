<?php

namespace App\Helpers\Socket;

class NotifyInterface
{
    /**
     * 格式化对话的消息体
     *
     * @param array $data 对话的消息
     * @return array
     */
    public static function formatTalkMsg(array $data)
    {
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

    /**
     * 消息加密方法
     */
    public static function encode()
    {

    }

    /**
     * 消息解密方法
     */
    public static function decode()
    {

    }
}
