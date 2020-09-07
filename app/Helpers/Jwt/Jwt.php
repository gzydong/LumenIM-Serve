<?php
namespace App\Helpers\Jwt;

class Jwt
{
    private static $instance;

    private $secretKey = 'lumen-im';

    public const ALG_METHOD_AES = 'AES';
    public const ALG_METHOD_HMACSHA256 = 'HMACSHA256';
    public const ALG_METHOD_HS256 = 'HS256';

    public static function getInstance():Jwt
    {
        if(!isset(self::$instance)){
            self::$instance = new Jwt();
        }

        return self::$instance;
    }

    public function setSecretKey(string $key):Jwt
    {
        $this->secretKey = $key;
        return $this;
    }

    public function publish():JwtObject
    {
        return new JwtObject(['secretKey' => $this->secretKey]);
    }

    public function decode(?string $raw):?JwtObject
    {
        $items = explode('.', $raw);

        // token格式
        if (count($items) !== 3) {
            throw new Exception('Token format error!');
        }

        // 验证header
        $header = Encryption::base64UrlDecode($items[0]);
        $header = json_decode($header, true);
        if (empty($header)) {
            throw new Exception('Token header is empty!');
        }

        // 验证payload
        $payload = Encryption::base64UrlDecode($items[1]);
        $payload = json_decode($payload, true);
        if (empty($header)) {
            throw new Exception('Token payload is empty!');
        }

        if(empty($items[2])){
            throw new Exception('signature is empty');
        }

        $jwtObjConfig = array_merge(
            $header,
            $payload,
            [
                'signature' => $items[2],
                'secretKey' => $this->secretKey
            ]
        );

        return new JwtObject($jwtObjConfig,true);
    }

}
