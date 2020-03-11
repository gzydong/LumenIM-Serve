<?php

namespace App\Http\Controllers;

use App\Facades\WebSocketHelper;
use App\Facades\ChatService;
use App\Models\EmoticonDetails;
use App\Models\UsersChatFiles;

/**
 *
 * Class SocketController
 * @package App\Http\Controllers
 */
class SocketController extends Controller
{
    /**
     * 格式化聊天消息
     *
     * @param array $message
     * @return array
     */
    private function formatMessage(array $message){
        $data = [
            'id'=>'',
            'msg_type'=>'',
            'source'=>'',
            'user_id'=>'',
            'receive_id'=>'',
            'float'=>'',
            'avatar'=>'',
            'nickname'=>'',
            'friend_remarks'=>'',
            'send_time'=>'',
            'content'=>'',
            'file_id'=>'',
            'file_original_name'=>'',
            'file_size'=>'',
            'file_suffix'=>'',
            'file_type'=>'',
            'file_url'=>'',
            'flie_source'=>'',
            'save_dir'=>''
        ];

        return $data;
    }

    private function sendData(){
        return [
            'send_user'=>0,       //发送消息的用户ID
            'receive_user'=> 0,   //接受者消息ID(用户ID或群ID)
            'chat_type'=>1,       //聊天类型  1:私聊     2:群聊
            'msg_type'=>1,        //消息类型  1:文本消息 2:文件消息 3:表情包消息
            'text_message'=>'',   //文本消息内容
            'file_message'=>0     //文件消息或表情包消息
        ];
    }

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

        $msgData['send_time'] = date('Y-m-d H:i:s');

        //获取客户端绑定的用户ID
        $uid = WebSocketHelper::getFdUserId($fd);

        //检测发送者与客户端是否是同一个用户
        if ($uid != $msgData['send_user']) {
            WebSocketHelper::sendResponseMessage('notify', $fd, ['notify' => '非法操作!!!']);
            return true;
        }

        //验证消息类型 私聊|群聊
        if (!in_array($msgData['source_type'], [1, 2]) || !in_array($msgData['msg_type'], [1,2,3,4,5])) {
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


        var_dump($msgData);

        return true;

        //处理文本消息
        if ($msgData['msg_type'] == 1) {
            $msgData["content"] = htmlspecialchars($msgData['content']);
        } else if ($msgData['msg_type'] == 2) {
            $fileId = decrypt($msgData["content"]);
            if (!$fileId)  return true;

            $msgData["content"] = '';
            $msgData['file_id'] = $fileId;
        }else if ($msgData['msg_type'] == 5) {
            $emoticonDetails = EmoticonDetails::select(['id','file_suffix','file_size','url'])->where('id',$msgData["content"])->first();
            $fileRes = UsersChatFiles::create([
                'user_id' => $uid,
                'flie_source'=>2,
                'file_type' => 1,
                'file_suffix' => $emoticonDetails->file_suffix,
                'file_size' => $emoticonDetails->file_size,
                'save_dir' => $emoticonDetails->url,
                'original_name' => '系统表情',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $msgData['msg_type'] = 2;
            $msgData['file_id'] = $fileRes->id;
            $msgData["content"] = '';
        }

        //将聊天记录保存到数据库(待优化：后面采用异步保存信息)
        if (!$insert_id = ChatService::saveChatRecord($msgData)) {
            info("聊天记录保存失败：" . json_encode($msgData));
        }

        //获取消息接收的客户端
        $clientFds = [];
        if ($msgData['source_type'] == 1) {//私聊
            $clientFds = WebSocketHelper::getUserFds($msgData['receive_user']);
        } else if ($msgData['source_type'] == 2) {
            $clientFds = WebSocketHelper::getRoomGroupName($msgData['receive_user']);
        }

        //用户信息
        $userInfo = [
            'user_id' => $msgData['send_user'],//用户ID
            'avatar' => '',//用户头像
            'nickname' => '',//用户昵称
            'remark_nickname' => ''//好友备注或用户群名片
        ];

        //获取群聊用户信息
        if ($msgData['source_type'] == 2) {
            if ($info = ChatService::getUsersGroupMemberInfo($msgData['receive_user'], $msgData['send_user'])) {
                $userInfo['avatar'] = $info['avatar'];
                $userInfo['nickname'] = $info['nickname'];
                $userInfo['remark_nickname'] = $info['visit_card'];
            }
        }

        //消息发送者用户信息
        $msgData['id'] = $insert_id;
        $msgData['sendUserInfo'] = $userInfo;
        $msgData["fileInfo"] = [];

        //替换表情
        if ($msgData['msg_type'] == 1) {
            $msgData["content"] = emojiReplace($msgData['content']);
        } else if ($msgData['msg_type'] == 2){
            $fileInfo = UsersChatFiles::where('id', $msgData['file_id'])->first(['file_type', 'file_suffix', 'file_size', 'save_dir', 'original_name']);
            if ($fileInfo) {
                $msgData["fileInfo"]['file_type'] = $fileInfo->file_type;
                $msgData["fileInfo"]['file_suffix'] = $fileInfo->file_suffix;
                $msgData["fileInfo"]['file_size'] = $fileInfo->file_size;
                $msgData["fileInfo"]['original_name'] = $fileInfo->original_name;
                $msgData["fileInfo"]['url'] = $fileInfo->file_type == 1 ? getFileUrl($fileInfo->save_dir) : '';
            }

            unset($msgData['file_id']);
        }else if ($msgData['msg_type'] == 5){
            $fileInfo = EmoticonDetails::where('id', $msgData['file_id'])->first(['url']);
            if ($fileInfo) {
                $msgData["fileInfo"]['file_type'] = 1;
                $msgData["fileInfo"]['file_suffix'] = '';
                $msgData["fileInfo"]['file_size'] = '';
                $msgData["fileInfo"]['original_name'] = '';
                $msgData["fileInfo"]['url'] = getFileUrl($fileInfo->url);
            }

            unset($msgData['file_id']);
        }

        unset($msgData['fd']);

        //发送消息
        WebSocketHelper::sendResponseMessage('chat_message', $clientFds, $msgData);
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


