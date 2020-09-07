<?php
namespace App\Helpers\Jwt;

use Illuminate\Support\Str;

class JwtObject extends SplBean
{
    public const STATUS_OK = 1;
    public const STATUS_SIGNATURE_ERROR = -1;
    public const STATUS_EXPIRED = -2;

    protected $alg = Jwt::ALG_METHOD_HMACSHA256; // 加密方式
    protected $iss = 'EasySwoole'; // 发行人
    protected $exp; // 到期时间
    protected $sub; // 主题
    protected $nbf; // 在此之前不可用
    protected $aud; // 用户
    protected $iat; // 发布时间
    protected $jti; // JWT ID用于标识该JWT
    protected $signature;
    protected $status = 0;
    protected $data;

    protected $secretKey;
    protected $header;
    protected $payload;

    protected function initialize(): void
    {
        if(empty($this->nbf)){
            $this->nbf = time();
        }
        if(empty($this->iat)){
            $this->iat = time();
        }
        if(empty($this->exp)){
            $this->exp = time() + 7200;
        }
        if(empty($this->jti)){
            $this->jti = Str::random(10);
        }

        // 解包：验证签名
        if(!empty($this->signature)){
            $signature = $this->signature();
            if($this->signature !== $signature){
                $this->status = self::STATUS_SIGNATURE_ERROR;
                return;
            }
            if(time() > $this->exp){
                $this->status = self::STATUS_EXPIRED;
                return;
            }
        }
        $this->status = self::STATUS_OK;
    }

    /**
     * @return mixed
     */
    public function getAlg()
    {
        return $this->alg;
    }

    /**
     * @param mixed $alg
     * @return JwtObject
     */
    public function setAlg($alg): self
    {
        $this->alg = $alg;

        return $this;
    }

    /**
     * @return string
     */
    public function getIss(): string
    {
        return $this->iss;
    }

    /**
     * @param string $iss
     * @return JwtObject
     */
    public function setIss(string $iss): self
    {
        $this->iss = $iss;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExp()
    {
        return $this->exp;
    }

    /**
     * @param mixed $exp
     * @return JwtObject
     */
    public function setExp($exp): self
    {
        $this->exp = $exp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSub()
    {
        return $this->sub;
    }

    /**
     * @param mixed $sub
     * @return JwtObject
     */
    public function setSub($sub): self
    {
        $this->sub = $sub;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNbf()
    {
        return $this->nbf;
    }

    /**
     * @param mixed $nbf
     * @return JwtObject
     */
    public function setNbf($nbf): self
    {
        $this->nbf = $nbf;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAud()
    {
        return $this->aud;
    }

    /**
     * @param mixed $aud
     * @return JwtObject
     */
    public function setAud($aud): self
    {
        $this->aud = $aud;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIat()
    {
        return $this->iat;
    }

    /**
     * @param mixed $iat
     * @return JwtObject
     */
    public function setIat($iat): self
    {
        $this->iat = $iat;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getJti()
    {
        return $this->jti;
    }

    /**
     * @param mixed $jti
     * @return JwtObject
     */
    public function setJti($jti): self
    {
        $this->jti = $jti;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param mixed $data
     * @return JwtObject
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param mixed $secretKey
     * @return JwtObject
     */
    public function setSecretKey($secretKey): self
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    public function setHeader()
    {
        //TODO:: 为了兼容老版本做了映射
        $algMap = [
            Jwt::ALG_METHOD_HMACSHA256 => Jwt::ALG_METHOD_HS256,
            Jwt::ALG_METHOD_AES => Jwt::ALG_METHOD_AES,
            Jwt::ALG_METHOD_HS256 => Jwt::ALG_METHOD_HS256
        ];

        $header = json_encode([
            'alg' => $algMap[$this->getAlg()],
            'typ' => 'JWT'
        ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $this->header = Encryption::base64UrlEncode($header);
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setPayload()
    {
        $payload = json_encode([
            'exp' => $this->getExp(),
            'sub' => $this->getSub(),
            'nbf' => $this->getNbf(),
            'aud' => $this->getAud(),
            'iat' => $this->getIat(),
            'jti' => $this->getJti(),
            'status' => $this->getStatus(),
            'data' => $this->getData()
        ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $this->payload = Encryption::base64UrlEncode($payload);
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function signature():?string
    {

        $this->setHeader();
        $this->setPayload();

        $content = $this->getHeader() . '.' . $this->getPayload();

        switch ($this->getAlg()){
            case Jwt::ALG_METHOD_HMACSHA256:
                $signature = Encryption::base64UrlEncode(
                    hash_hmac('sha256', $content, $this->getSecretKey(), true)
                );
                break;
            case Jwt::ALG_METHOD_HS256:
                $signature = Encryption::base64UrlEncode(
                    hash_hmac('sha256', $content, $this->getSecretKey(), true)
                );
                break;
            case Jwt::ALG_METHOD_AES:
                $signature = Encryption::base64UrlEncode(
                    openssl_encrypt($content, 'AES-128-ECB', $this->getSecretKey())
                );
                break;
        }

        return $signature;
    }

    public function __toString()
    {
        $this->signature = $this->signature();

        return $this->header . '.' . $this->payload . '.' . $this->signature;
    }
}
