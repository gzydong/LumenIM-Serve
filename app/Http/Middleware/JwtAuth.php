<?php
namespace App\Http\Middleware;

use Closure;
use App\Helpers\JwtAuth as Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class JwtAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            $token = Auth::parseToken();
            if(empty($token)){
                throw new UnauthorizedHttpException('jwt-auth', 'Token not provided');
            }

            $auth = Auth::getInstance();
            $auth->setToken($token);
            if($auth->validate() && $auth->verify()){
                return $next($request);
            }else{
                //token 已过期
                if($auth->decode()->isExpired()){
                    throw new UnauthorizedHttpException('jwt-auth', 'Token 授权已过期');
                }else{//token 验证失败
                    throw new UnauthorizedHttpException('jwt-auth', 'Token 授权验证失败');
                }
            }
        }catch (\InvalidArgumentException $e){
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage());
        }catch (\RuntimeException $e){
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage());
        }catch (\Exception $e){
            throw new UnauthorizedHttpException('jwt-auth', 'Token not provided');
        }

        return $next($request);
    }
}
