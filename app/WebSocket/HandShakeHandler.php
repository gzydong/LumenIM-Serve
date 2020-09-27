<?php

namespace App\WebSocket;

use App\Helpers\JwtObject;

/**
 * Websocket 自定义挥手处理
 *
 * Class HandShakeHandler
 * @package App\Services\Websocket
 */
class HandShakeHandler
{
    /**
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     *
     * @return bool
     */
    public function handle($request, $response)
    {
        $token = $request->get['token'] ?? '';
        try {
            $jwtObject = app('jwt.auth')->decode($token);
            $status = $jwtObject->getStatus();

            if ($status == JwtObject::STATUS_SIGNATURE_ERROR) {
                throw new \Exception('Token 授权验证失败');
            } else if ($status == JwtObject::STATUS_EXPIRED) {
                throw new \Exception('Token 授权已过期');
            }
        } catch (\Exception $e) {
            $response->status(401);
            $response->end();
            return false;
        }

        $socketkey = $request->header['sec-websocket-key'];
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $socketkey) || 16 !== strlen(base64_decode($socketkey))) {
            $response->end();
            return false;
        }

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => base64_encode(sha1($socketkey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)),
            'Sec-WebSocket-Version' => '13',
        ];

        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $header => $val) {
            $response->header($header, $val);
        }

        $response->status(101);
        $response->end();

        return true;
    }
}
