<?php

namespace App\Helpers;

/**
 * Class EncryptionHelper
 * @package App\Helpers
 */
class Encryption
{
    /**
     * Url  base64_encode 加密
     *
     * @param string $content 加密的url
     * @return string
     */
    public static function base64UrlEncode(string $content)
    {
        return str_replace('=', '', strtr(base64_encode($content), '+/', '-_'));
    }

    /**
     * Url base64_decode 解密
     *
     * @param string $content 解密字符串
     * @return string
     */
    public static function base64UrlDecode(string $content)
    {
        $remainder = strlen($content) % 4;
        if ($remainder) {
            $content .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($content, '-_', '+/'));
    }

    /**
     * rsa加密
     * openssl genrsa -out rsa_private_key.pem 1024 // 生成原始 RSA私钥文件 rsa_private_key.pem
     * openssl pkcs8 -topk8 -inform PEM -in rsa_private_key.pem -outform PEM -nocrypt -out private_key.pem // 将原始 RSA私钥转换为 pkcs8格式
     * openssl rsa -in rsa_private_key.pem -pubout -out rsa_public_key.pem // 生成RSA公钥 rsa_public_key.pem
     *
     * @param string $data 数据
     * @param string $rsaPrivateKey 私钥PEM文件的绝对路径
     * @return string
     */
    public static function rsaEnCode(string $data, string $rsaPrivateKey)
    {
        /* 获取私钥PEM文件内容 */
        $priKey = file_get_contents($rsaPrivateKey);
        /* 从PEM文件中提取私钥 */
        $res = openssl_get_privatekey($priKey);
        /* 对数据进行签名 */
        //openssl_sign($data, $sign, $res);
        openssl_private_encrypt($data, $sign, $res);
        /* 释放资源 */
        openssl_free_key($res);
        /* 对签名进行Base64编码，变为可读的字符串 */
        $sign = base64_encode($sign);

        return $sign;
    }

    /**
     * rsa解密
     *
     * @param string $data 加密后的数据
     * @param string $rsaPublicKey 公钥PEM文件的绝对路径
     * @return mixed
     */
    public static function rsaDeCode(string $data, string $rsaPublicKey)
    {
        /* 获取公钥PEM文件内容 */
        $pubKey = file_get_contents($rsaPublicKey);
        /* 从PEM文件中提取公钥 */
        $res = openssl_get_publickey($pubKey);
        /* 对数据进行解密 */
        openssl_public_decrypt(base64_decode($data), $decrypted, $res);
        /* 释放资源 */
        openssl_free_key($res);

        return $decrypted;
    }
}
