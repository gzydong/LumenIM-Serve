<?php

namespace App\Http\Controllers\Socket;

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

        $socket = app('SocketFdUtil');

        //获取客户端绑定的用户ID
        $uid = $socket->getFdUserId($fd);

        //检测发送者与客户端是否是同一个用户
        if ($uid != $msgData['send_user']) {
            $socket->sendResponseMessage('notify', $fd, ['notify' => '非法操作!!!']);
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
                $socket->sendResponseMessage('notify', $fd, ['notify' => '温馨提示:您当前与对方尚未成功好友！']);
                return true;
            }
        } else if ($msgData['source_type'] == 2) {//群聊
            //判断是否属于群成员
            if (!ChatService::checkGroupMember($msgData['receive_user'], $msgData['send_user'])) {
                $socket->sendResponseMessage('notify', $fd, ['notify' => '温馨提示:您还没有加入该聊天群！']);
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
            'id' => $insert_id,//消息记录ID
            'msg_type' => $record['msg_type'],
            'source' => $msgData['source_type'],
            'user_id' => $msgData['send_user'],
            'receive_id' => $msgData['receive_user'],
            'send_time' => $record['send_time']
        ];

        //获取群聊用户信息
        if ($msgData['source_type'] == 2) {
            if ($info = ChatService::getUsersGroupMemberInfo($msgData['receive_user'], $msgData['send_user'])) {
                $push_message['avatar'] = $info['avatar'];
                $push_message['nickname'] = $info['nickname'];
                $push_message['friend_remarks'] = $info['visit_card'];
            }
        }

        if ($record['msg_type'] == 1) {
            $push_message['content'] = emojiReplace($record['content']); //替换表情
        } else if ($record['msg_type'] == 2) {
            if ($fileInfo = UsersChatFiles::where('id', $record['file_id'])->first(['file_type', 'file_suffix', 'file_size', 'save_dir', 'original_name'])) {
                $push_message['file_id'] = $record['file_id'];
                $push_message['file_type'] = $fileInfo->file_type;
                $push_message['file_suffix'] = $fileInfo->file_suffix;
                $push_message['file_size'] = $fileInfo->file_size;
                $push_message['file_original_name'] = $fileInfo->original_name;
                $push_message['file_url'] = $fileInfo->file_type == 1 ? getFileUrl($fileInfo->save_dir) : '';
            }
        }

        //获取消息推送的客户端
        $clientFds = [];
        if ($msgData['source_type'] == 1) {//私聊
            $clientFds = array_unique(array_merge($socket->getUserFds($msgData['receive_user']), $socket->getUserFds($msgData['send_user'])));
        } else if ($msgData['source_type'] == 2) {
            $clientFds = $socket->getRoomGroupName($msgData['receive_user']);
        }

        $socket->sendResponseMessage('chat_message', $clientFds, ChatService::getChatMessage(
            $msgData['send_user'],
            $msgData['receive_user'],
            $msgData['source_type'],
            $record['msg_type'],
            $push_message
        ));

        $push_message =  $msgData = $clientFds = $record = $socket = null;
    }

    /**
     * 键盘输入提示
     *
     * @param $websocket
     * @param $msgData
     */
    public function inputTipPush($websocket, $msgData)
    {
        $socket = app('SocketFdUtil');
        $clientFds = $socket->getUserFds($msgData['receive_user']);
        if ($clientFds) {
            $socket->sendResponseMessage('input_tip', $clientFds, $msgData);
        }
    }
}
