<?php

namespace App\Http\Middleware;

use App\Support\JwtObject;
use Closure;

class JwtAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = parse_token();
        if (empty($token)) {
            return response()->json(['code' => 401, 'msg' => 'Token not provided'], 401);
        }

        try {
            $jwtObject = app('jwt.auth')->decode($token);
            $status = $jwtObject->getStatus();

            if ($status == JwtObject::STATUS_SIGNATURE_ERROR) {
                throw new \Exception('Token 验证失败');
            } else if ($status == JwtObject::STATUS_EXPIRED) {
                throw new \Exception('Token 已过期');
            }

            // 验证是否是黑名单
            if (app('jwt.auth')->isBlackList($token)) {
                throw new \Exception('Token 已失效');
            }
        } catch (\Exception $e) {
            return response()->json(['code' => 401, 'msg' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
