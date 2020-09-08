<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Helpers\Jwt\JwtObject;

/**
 * @method bool joinBlackList(string $token) 加入黑名单
 * @method bool isBlackList(string $token)   判断是否已加入黑名单
 *
 * Class JwtAuthFacade
 * @package App\Facades
 */
class JwtAuthFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jwt.auth';
    }

    /**
     *  获取jwt实例
     *
     * @return JwtObject
     */
    static function jwtObject()
    {
        return app('jwt.auth')->jwtObject();
    }

    /**
     * 解析token
     *
     * @param $token
     * @return JwtObject
     * @throws \Exception
     */
    static function decode($token)
    {
        return app('jwt.auth')->decode($token);
    }
}
