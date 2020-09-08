<?php

namespace App\Http\Middleware;

use App\Facades\JwtAuthFacade;
use App\Helpers\Jwt\JwtObject;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
        $token = parseToken();
        if (empty($token)) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token not provided');
        }

        try {
            $jwtObject = JwtAuthFacade::decode($token);
            $status = $jwtObject->getStatus();

            if ($status == JwtObject::STATUS_SIGNATURE_ERROR) {
                throw new \Exception('Token 授权验证失败');
            } else if ($status == JwtObject::STATUS_EXPIRED) {
                throw new \Exception('Token 授权已过期');
            }

            // 验证是否是黑名单
            if (app('jwt.auth')->isBlackList($token)) {
                throw new \Exception('Token 已失效');
            }
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage());
        }

        return $next($request);
    }
}
