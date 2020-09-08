<?php

namespace App\Services;

use \Exception;
use App\Helpers\Jwt\JwtObject;

/**
 * jwt 授权服务
 * Class JwtAuthService
 * @package App\Services
 */
class JwtAuthService
{
    const BlackListPrefix = 'jwt:token_blacklist:';

    /**
     * 获取jwt秘钥
     *
     * @return mixed
     */
    private function _secret()
    {
        return config('config.jwt_secret');
    }

    /**
     * 加入黑名单
     *
     * @param string $token jwt 授权token
     * @param float|int $exp 过期时间
     */
    public function joinBlackList(string $token, $exp = 60 * 60 * 24 * 2)
    {
        $this->getRedis()->setex(self::BlackListPrefix . $token, $exp, 1);
    }

    /**
     * 判断是否是黑名单
     *
     * @param string $token
     * @return bool
     */
    public function isBlackList(string $token)
    {
        return $this->getRedis()->get(self::BlackListPrefix . $token) ? true : false;
    }

    /**
     * 获取jwt实例
     *
     * @return JwtObject
     */
    public function jwtObject()
    {
        return new JwtObject($this->_secret());
    }

    /**
     * 解析jwt token信息
     *
     * @param string $token
     * @return JwtObject
     * @throws Exception
     */
    public function decode(string $token)
    {
        $items = explode('.', $token);

        // token格式
        if (count($items) !== 3) {
            throw new Exception('Token format error!', 1001);
        }

        // 验证header
        $header = json_decode(base64UrlDecode($items[0]), true);
        if (empty($header)) {
            throw new Exception('Token header is empty!', 1002);
        }

        // 验证payload
        $payload = json_decode(base64UrlDecode($items[1]), true);
        if (empty($header)) {
            throw new Exception('Token payload is empty!', 1003);
        }

        if (empty($items[2])) {
            throw new Exception('signature is empty', 1004);
        }

        $jwtObjConfig = array_merge($header, $payload, ['signature' => $items[2]]);

        return new JwtObject($this->_secret(), $jwtObjConfig);
    }

    /**
     * 获取Redis 实例
     *
     * @return mixed
     */
    private function getRedis()
    {
        return app('redis');
    }
}
