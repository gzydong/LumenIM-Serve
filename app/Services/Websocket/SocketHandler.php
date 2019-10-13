<?php
namespace App\Services\Websocket;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Websocket\SocketIO\WebsocketHandler;

use App\Console\Facades\WebSocketHelper;

class SocketHandler  extends WebsocketHandler
{

    /**
     * 连接成功方法
     * @param int $fd
     * @param Request $request
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        echo date('Y-m-d H:i:s')." {$fd}连接了".PHP_EOL;

        var_dump(Room::getClients('room.group.chat.1'));


        $user_id = $request->get('sid');
        if($fd == 1){
            WebSocketHelper::clearRedisCache();
        }

        //这里检测连接用户是否在其它地方登录（如果之前登录的fd 断开连接） 模拟查询
        $rfd = WebSocketHelper::getUserFd($user_id);//模拟用户其它地方登录的fd

        echo "user_id[$user_id] : rfd[{$rfd}]";
        if($rfd){
            //websocket 服务实例
            $wsServer = App::make(Server::class);
            if($wsServer->exist($rfd)){
                $wsServer->disconnect($rfd,4030, "您的账号在其他设备登录，如果这不是您的操作，请及时修改您的登录密码");
            }
        }

        //这里处理用户登录后的逻辑
        WebSocketHelper::bindUserFd($user_id,$fd);   //绑定用户ID与fd的关系
        WebSocketHelper::bindGroupChat($user_id,$fd);//绑定群聊关系

        echo PHP_EOL;
        var_dump(Room::getRooms($fd));
        echo PHP_EOL;

        var_dump(Room::getClients('room.group.chat.1'));

        return true;
    }

    /**
     * 消息接收方法
     * @param Frame $frame
     * @return bool|void
     */
    public function onMessage(Frame $frame)
    {
        $data = [
            'sourceType'=>1,

            //接收者信息
            'receiveUser'=> 'toUserId',

            //发送者ID
            'sendUser'=> '',

            //消息类型  1:文字消息  2:图片消息  3:文件消息
            'msgType'=>1,

            //文字消息
            'textMessage'=>'fasfa',

            //图片消息
            'imgMessage'=>'',

            //文件消息
            'fileMessage'=>'',
        ];

        Websocket::broadcast()->to('qunliao')->emit('message', json_encode($data));

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
