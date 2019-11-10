<?php
namespace App\Services\Websocket;

use Illuminate\Http\Request;
use App\Facades\WebSocketHelper;
use App\Facades\ChatService;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;
use App\Helpers\RsaMeans;

class SocketHandler  extends WebsocketHandler
{

    /**
     * 连接成功方法
     *
     * @param int $fd
     * @param Request $request
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        $user_id = RsaMeans::decrypt($request->get('sid'));
        if($fd == 1){
            WebSocketHelper::clearRedisCache();
        }

        //这里处理用户登录后的逻辑
        WebSocketHelper::bindUserFd($user_id,$fd);   //绑定用户ID与fd的关系
        WebSocketHelper::bindGroupChats($user_id,$fd);//绑定群聊关系
        return true;
    }

    /**
     * 消息接收方法
     * @param Frame $frame
     * @return bool|void
     */
    public function onMessage(Frame $frame)
    {
        $msgData = json_decode($frame->data,true);
        $msgData['send_time'] = date('Y-m-d H:i:s');

        //这里做消息处理
        if(!ChatService::check($msgData)){
            return true;
        }

        //将聊天记录保存到数据库(待优化：后面采用异步保存信息)
        if($packageData = ChatService::saveChatRecord($msgData)){
            info("聊天记录保存失败：".json_encode($msgData));
        }

        $receive = [];
        if($msgData['sourceType'] == 1){//私聊
            $receive = WebSocketHelper::getUserFds($msgData['receiveUser']);
        }else if($msgData['sourceType'] == 2){
            $receive = WebSocketHelper::getRoomGroupName($msgData['receiveUser']);
        }

        //发送消息
        WebSocketHelper::sendResponseMessage('chat_message',$receive,$msgData);
        return true;
    }

    /**
     * 这里需要将fd关闭后的相关数据清除掉
     * @param int $fd
     * @param int $reactorId
     * @return bool|void
     */
    public function onClose($fd, $reactorId)
    {

//        $uid = WebSocketHelper::getFdUserId($fd);
//        echo "用户ID:{$uid},FD:{$fd}".date('Y-m-d H:i:s').PHP_EOL;

        WebSocketHelper::clearFdCache($fd);
        return true;
    }
}
