<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/9/20
 * Time: 17:06
 */

namespace App\Http\Middleware;

use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Closure;

class TokenAuth extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        dd('asd');
        $this->authenticate($request);

        return $next($request);
    }
}