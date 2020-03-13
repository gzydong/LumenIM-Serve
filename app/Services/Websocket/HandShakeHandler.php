<?php
namespace App\Services\Websocket;

use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
/**
 * Websocket 自定义挥手处理
 *
 * Class HandShakeHandler
 * @package App\Services\Websocket
 */
class HandShakeHandler
{
    /**
     * https://learnku.com/articles/10885/full-use-of-jwt
     * @see https://www.swoole.co.uk/docs/modules/swoole-websocket-server
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     *
     * @return bool
     */
    public function handle($request, $response)
    {

        $token = JWTAuth::getToken();
        var_dump(Auth::guard('api')->check(),$token);
        if(!Auth::guard('api')->check()){
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
