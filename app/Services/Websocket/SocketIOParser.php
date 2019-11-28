<?php
namespace App\Services\Websocket;

use SwooleTW\Http\Websocket\Parser;
use SwooleTW\Http\Websocket\SocketIO\Packet;

/**
 * 消息接收过滤处理类
 *
 * @package App\Services\Websocket
 */
class SocketIOParser extends Parser
{

    /**
     * 检测是否跳过消息处理(true:跳过消息处理  false:接收消息处理)
     *
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame $frame
     * @return bool
     */
    public function execute($server, $frame) :bool
    {
        //判断接收的消息是否为心跳检测
        if($frame->data == 'heartbeat'){
            return true;
        }

        $skip = false;
        $data = json_decode($frame->data,true);
        if(!$data || !array_has($data,['sourceType','receiveUser','sendUser','msgType','textMessage'])){
            return true;
        }

        return $skip;
    }

    /**
     * Encode output payload for websocket push.
     * 消息推送给客户端时的数据加密方法
     *
     * @param string $event
     * @param mixed $data
     *
     * @return mixed
     */
    public function encode(string $event, $data)
    {
        $packet = Packet::MESSAGE . Packet::EVENT;
        $shouldEncode = is_array($data) || is_object($data);
        $data = $shouldEncode ? json_encode($data) : $data;
        $format = $shouldEncode ? '["%s",%s]' : '["%s","%s"]';

        return $packet . sprintf($format, $event, $data);
    }

    /**
     * Decode message from websocket client.
     * Define and return payload here.
     *
     * @param \Swoole\Websocket\Frame $frame
     *
     * @return array
     */
    public function decode($frame)
    {
        //$payload = Packet::getPayload($frame->data);
        return [
            //事件名称请查看 App\Services\Websocket\SocketHandler 中自定义的方法
            'event' => 'onMessage',
            'data' => $frame->data ?? null
        ];
    }
}
