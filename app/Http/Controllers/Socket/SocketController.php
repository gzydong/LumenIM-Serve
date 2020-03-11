<?php

namespace App\Http\Controllers\Socket;

use App\Facades\WebSocketHelper;
use App\Facades\ChatService;
use App\Models\EmoticonDetails;
use App\Models\UsersChatFiles;
use App\Http\Controllers\Controller;

/**
 * Class SocketController
 *
 * @package App\Http\Controllers
 */
class SocketController extends Controller
{
    /**
     * 聊天数据处理
     *
     * @param $websocket
     * @param $msgData
     * @return bool
     */
    public function chatDialogue($websocket, $msgData)
    {
        $fd = $msgData['fd'];
        //获取客户端绑定的用户ID
        $uid = WebSocketHelper::getFdUserId($fd);

        //检测发送者与客户端是否是同一个用户
        if ($uid != $msgData['send_user']) {
            WebSocketHelper::sendResponseMessage('notify', $fd, ['notify' => '非法操作!!!']);
            return true;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($msgData['source_type'], [1, 2]) || !in_array($msgData['msg_type'], [1, 2, 3])) {
            return true;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        if ($msgData['source_type'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!ChatService::checkFriends($msgData['send_user'], $msgData['receive_user'])) {
                WebSocketHelper::sendResponseMessage('notify', $fd, ['notify' => '温馨提示:您当前与对方尚未成功好友！']);
                return true;
            }
        } else if ($msgData['source_type'] == 2) {//群聊
            //判断是否属于群成员
            if (!ChatService::checkGroupMember($msgData['receive_user'], $msgData['send_user'])) {
                WebSocketHelper::sendResponseMessage('notify', $fd, ['notify' => '温馨提示:您还没有加入该聊天群！']);
                return true;
            }
        }

        //保存的消息记录
        $record = [
            'source' => $msgData['source_type'],
            'msg_type' => 0,
            'user_id' => $msgData['send_user'],
            'receive_id' => $msgData['receive_user'],
            'content' => '',
            'file_id' => 0,
            'send_time' => date('Y-m-d H:i:s')
        ];

        switch ($msgData['msg_type']) {
            case 1://文本消息
                $record['msg_type'] = 1;
                $record["content"] = htmlspecialchars($msgData['text_message']);
                break;
            case 2://文件消息
                $record['msg_type'] = 2;
                $record['file_id'] = $msgData['file_message'];
                break;
            case 3://系统表情包
                $emoticonDetails = EmoticonDetails::select(['id', 'file_suffix', 'file_size', 'url'])->where('id', $msgData["file_message"])->first();
                $fileRes = UsersChatFiles::create([
                    'user_id' => $uid,
                    'flie_source' => 2,
                    'file_type' => 1,
                    'file_suffix' => $emoticonDetails->file_suffix,
                    'file_size' => $emoticonDetails->file_size,
                    'save_dir' => $emoticonDetails->url,
                    'original_name' => '系统表情',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $record['msg_type'] = 2;
                $record['file_id'] = $fileRes->id;
                break;
        }

        //将聊天记录保存到数据库(待优化：后面采用异步保存信息)
        if (!$insert_id = ChatService::saveChatRecord($record)) {
            info("聊天记录保存失败：" . json_encode($record));
        }

        //推送的数据包
        $push_message = [
            'send_user' => $msgData['send_user'],       //发送消息的用户ID
            'receive_user' => $msgData['receive_user'],   //接受者消息ID(用户ID或群ID)
            'source_type' => $msgData['source_type'],       //聊天类型  1:私聊     2:群聊
            'msg_type' => $record['msg_type'],        //消息类型  1:文本消息 2:文件消息
            'data' => [
                'id' => $insert_id,//消息记录ID
                'msg_type' => $record['msg_type'],
                'source' => $msgData['source_type'],
                'user_id' => $msgData['send_user'],
                'receive_id' => $msgData['receive_user'],
                'send_time' => $record['send_time'],

                //发送者个人信息
                'avatar' => '',
                'nickname' => '',
                'friend_remarks' => '',

                //文本消息信息
                'content' => '',

                //文件消息信息
                'file_id' => $record['file_id'],
                'file_original_name' => '',
                'file_size' => '',
                'file_suffix' => '',
                'file_type' => '',
                'file_url' => '',
                'flie_source' => ''
            ]
        ];

        $msgData = null;

        //获取群聊用户信息
        if ($push_message['source_type'] == 2) {
            if ($info = ChatService::getUsersGroupMemberInfo($push_message['receive_user'], $push_message['send_user'])) {
                $push_message['data']['avatar'] = $info['avatar'];
                $push_message['data']['nickname'] = $info['nickname'];
                $push_message['data']['friend_remarks'] = $info['visit_card'];
            }
        }

        //替换表情
        if ($push_message['msg_type'] == 1) {
            $push_message['data']["content"] = emojiReplace($record['content']);
        } else if ($push_message['msg_type'] == 2) {
            if ($fileInfo = UsersChatFiles::where('id', $record['file_id'])->first(['file_type', 'file_suffix', 'file_size', 'save_dir', 'original_name'])) {
                $push_message['data']['file_type'] = $fileInfo->file_type;
                $push_message['data']['file_suffix'] = $fileInfo->file_suffix;
                $push_message['data']['file_size'] = $fileInfo->file_size;
                $push_message['data']['file_original_name'] = $fileInfo->original_name;
                $push_message['data']['file_url'] = $fileInfo->file_type == 1 ? getFileUrl($fileInfo->save_dir) : '';
            }
        }

        //获取消息推送的客户端
        $clientFds = [];
        if ($push_message['source_type'] == 1) {//私聊
            $clientFds = WebSocketHelper::getUserFds($push_message['receive_user']);
        } else if ($push_message['source_type'] == 2) {
            $clientFds = WebSocketHelper::getRoomGroupName($push_message['receive_user']);
        }

        WebSocketHelper::sendResponseMessage('chat_message', $clientFds, $push_message);
    }

    /**
     * 键盘输入提示
     *
     * @param $websocket
     * @param $msgData
     */
    public function inputTipPush($websocket, $msgData)
    {
        $clientFds = WebSocketHelper::getUserFds($msgData['receive_user']);
        if ($clientFds) {
            WebSocketHelper::sendResponseMessage('input_tip', $clientFds, $msgData);
        }
    }
}
