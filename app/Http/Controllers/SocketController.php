<?php
namespace App\Http\Controllers;

use App\Facades\WebSocketHelper;
use App\Facades\ChatService;
use App\Models\UsersChatFiles;

/**
 *
 * Class SocketController
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
    public function chatDialogue($websocket, $msgData){
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
        if (!in_array($msgData['source_type'], [1, 2])){
            return true;
        }

        //验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
        if ($msgData['source_type'] == 1) {//私信
            //判断发送者和接受者是否是好友关系
            if (!ChatService::checkFriends($msgData['send_user'], $msgData['receive_user'])) {
                WebSocketHelper::sendResponseMessage('notify', $fd, ['notify'=>'温馨提示:您当前与对方尚未成功好友！']);
                return true;
            }
        } else if ($msgData['source_type'] == 2) {//群聊
            //判断是否属于群成员
            if (!ChatService::checkGroupMember($msgData['receive_user'], $msgData['send_user'])) {
                WebSocketHelper::sendResponseMessage('notify', $fd, ['notify'=>'温馨提示:您还没有加入该聊天群！']);
                return true;
            }
        }

        //处理文本消息
        if ($msgData['msg_type'] == 1) {
            $msgData["content"] = htmlspecialchars($msgData['content']);
        }else if($msgData['msg_type'] == 2){
            $fileId = decrypt($msgData["content"]);
            if(!$fileId){
                return true;
            }

            $msgData["content"] = '';
            $msgData['file_id']= $fileId;
        }


        //将聊天记录保存到数据库(待优化：后面采用异步保存信息)
        if (!$packageData = ChatService::saveChatRecord($msgData)) {
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
        $msgData['sendUserInfo'] = $userInfo;
        $msgData["fileInfo"] = [];

        //替换表情
        if ($msgData['msg_type'] == 1) {
            $msgData["content"] = emojiReplace($msgData['content']);
        }else{
            $msgData["content"] = emojiReplace($msgData['content']);
            $fileInfo = UsersChatFiles::where('id',$msgData['file_id'])->first(['file_type','file_suffix','file_size','save_dir','original_name']);
            if($fileInfo){
                $msgData["fileInfo"]['file_type'] = $fileInfo->file_type;
                $msgData["fileInfo"]['file_suffix'] = $fileInfo->file_suffix;
                $msgData["fileInfo"]['file_size'] = $fileInfo->file_size;
                $msgData["fileInfo"]['original_name'] = $fileInfo->original_name;
                $msgData["fileInfo"]['url'] = $fileInfo->file_type == 1 ? getFileUrl($fileInfo->save_dir) :'';
            }

            unset($msgData['file_id']);
        }

        unset($msgData['fd']);

        //发送消息
        WebSocketHelper::sendResponseMessage('chat_message', $clientFds, $msgData);
    }
}


