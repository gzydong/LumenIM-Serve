<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2020/4/11
 * Time: 15:25
 */

namespace App\Http\Middleware;

use Closure;
class ProxyAuth
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
        return $next($request);
    }
}
