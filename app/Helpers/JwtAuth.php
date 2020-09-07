<?php
namespace App\Helpers;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;

/**
 *
 * 单例模式 一次请求只针对一个用户.
 * Class JwtAuth
 * @package App\Helpers
 */
class JwtAuth
{
    // jwt参数
    private $iss = 'http://www.on-line-im.com/api';//该JWT的签发者
    private $aud = 'http://www.on-line-im.com';//配置听众
    private $id = 'sxs-4f1g23a12aa';//配置ID（JTI声明）

    // 加密后的token
    private $token;
    // 解析JWT得到的token
    private $decodeToken;
    // 用户ID
    private $uid;

    /**
     * 获取token
     * @return string
     */
    public function getToken($isString = true)
    {
        return $isString ? (string)$this->token :$this->token;
    }

    /**
     * 设置类内部 $token的值
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * 设置uid
     * @param $uid
     * @return $this
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * 得到 解密过后的 uid
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * 加密jwt
     * 
     * @return $this
     */
    public function encode()
    {
        $time = time();
        $this->token = (new Builder())
            ->setIssuer($this->iss)// 配置颁发者（iss声明）
            ->setAudience($this->aud)// 配置访问群体（aud claim）
            ->setId($this->id, true)// 配置id（jti声明），复制为头项 。 该jwt的唯一ID编号
            ->setIssuedAt($time)//配置令牌的发出时间（iat声明）
            ->setNotBefore($time)// 配置令牌可以使用的时间（nbf声明）
            ->setExpiration($time + 60*60*24*10)// 配置令牌的到期时间（exp claim）
            ->set('uid', $this->uid)// 配置一个名为“uid”的新声明
            ->sign(new Sha256(), $this->getSecrect())// 使用secrect作为密钥创建签名
            ->getToken(); // 检索生成的令牌
        return $this;
    }

    /**
     * 解密token
     * @return \Lcobucci\JWT\Token
     */
    public function decode()
    {
        if (!$this->decodeToken) {
            $this->decodeToken = (new Parser())->parse((string)$this->token);
            $this->uid = $this->decodeToken->getClaim('uid');
        }

        return $this->decodeToken;
    }


    private function getSecrect(){
        return config('config.jwt_secret');
    }

    /**
     * 验证令牌是否有效
     * @return bool
     */
    public function validate()
    {
        $data = new ValidationData();
        $data->setAudience($this->aud);
        $data->setIssuer($this->iss);
        $data->setId($this->id);
        return $this->decode()->validate($data);
    }

    /**
     * 验证令牌在生成后是否被修改
     * @return bool
     */
    public function verify()
    {
        $res = $this->decode()->verify(new Sha256(), $this->getSecrect());
        return $res;
    }

    public static function parseToken(){
        $token = app('request')->server->get('HTTP_AUTHORIZATION') ?: app('request')->server->get('REDIRECT_HTTP_AUTHORIZATION');
        if(!empty($token)){
            $token = str_replace('Bearer ','',$token);
        }else{
            $token = app('request')->get('token','');
        }

        return $token;
    }
}
