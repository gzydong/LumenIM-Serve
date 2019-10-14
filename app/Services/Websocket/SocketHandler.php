<?php
namespace App\Services\Websocket;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Facades\WebSocketHelper;
use App\Facades\ChatService;

use Swoole\Websocket\Frame;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

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
        //echo date('Y-m-d H:i:s')." {$fd}连接了".PHP_EOL;
        $user_id = $request->get('sid');
        if($fd == 1){
            WebSocketHelper::clearRedisCache();
        }

        //这里检测连接用户是否在其它地方登录（如果之前登录的fd 断开连接） 模拟查询
        $rfd = WebSocketHelper::getUserFd($user_id);//模拟用户其它地方登录的fd
        if($rfd){
            //echo "user_id[$user_id] : rfd[{$rfd}]";
            $wsServer = App::make(Server::class);
            if($wsServer->exist($rfd)){
                $wsServer->disconnect($rfd,4030, "您的账号在其他设备登录，如果这不是您的操作，请及时修改您的登录密码");
            }
        }

        //这里处理用户登录后的逻辑
        WebSocketHelper::bindUserFd($user_id,$fd);   //绑定用户ID与fd的关系
        WebSocketHelper::bindGroupChat($user_id,$fd);//绑定群聊关系

        return true;
    }

    /**
     * 消息接收方法
     * @param Frame $frame
     * @return bool|void
     */
    public function onMessage(Frame $frame)
    {
        /**
         * sourceType:发送类型(1:私信  2:群聊)
         * receiveUser:接收者信息
         * sendUser:发送者ID
         * msgType:消息类型(1:文字消息  2:图片消息  3:文件消息)
         * textMessage:文字消息
         * imgMessage:图片消息
         * fileMessage:文件消息
         */
        $msgData = json_decode($frame->data,true);
        $msgData['created_at'] = date('Y-m-d H:i:s');

        //这里做消息处理
        if(!ChatService::check($msgData)){
            return true;
        }

        //将聊天记录保存到数据库(后面采用异步保存信息)
        if(!ChatService::saveChatRecord($msgData)){
            info("聊天记录保存失败：".json_encode($msgData));
        }

        $receive = '';
        if($msgData['sourceType'] == 1){//私聊
            $receive = WebSocketHelper::getUserFd($msgData['receiveUser']);
        }else if($msgData['sourceType'] == 2){
            $receive = WebSocketHelper::getRoomGroupName($msgData['receiveUser']);
        }

//        var_dump($receive);

        //发送消息
        if($receive){
            Websocket::to($receive)->emit('message', json_decode($frame->data));
        }

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
        echo date('Y-m-d H:i:s')." {$fd} 关闭了连接 reactorId：{$reactorId}".PHP_EOL;

        WebSocketHelper::clearFdCache($fd);

        return true;
    }

    //这里可定义其他事件名
}
