<?php
namespace App\Helpers;

class RsaMeans
{
    /**
     * 公钥加密
     *
     * @param  string|array $originalData 待加密的数据
     * @return bool|string
     */
    public static function encrypt($originalData){
        if (self::checkSecretKey() && openssl_private_encrypt($originalData, $encryptData, self::getPrivateKey())) {
            return self::base64UrlEncode($encryptData);
        }

        return false;
    }

    /**
     * 私钥解密
     *
     * @param string $encryptData 加密后的字符串
     * @return bool|string
     */
    public static function decrypt(string $encryptData){
        $encryptData = self::base64UrlDecode($encryptData);
        if(self::checkSecretKey() &&  openssl_public_decrypt($encryptData, $decryptData, self::getPublicKey())){
            return $decryptData;
        }

        return false;
    }

    /**
     * 生成Resource类型的密钥，如果密钥文件内容被破坏，openssl_pkey_get_private函数返回false
     *
     * @return resource
     */
    private static function getPrivateKey(){
        return openssl_pkey_get_private(file_get_contents(base_path('RsaSecretkey/rsa_private_key.pem')));
    }

    /**
     * 生成Resource类型的公钥，如果公钥文件内容被破坏，openssl_pkey_get_public函数返回false
     *
     * @return resource
     */
    private static function getPublicKey(){
        return openssl_pkey_get_public(file_get_contents(base_path('RsaSecretkey/rsa_public_key.pem')));
    }

    /**
     * 检测公钥私钥是否可用
     */
    public static function checkSecretKey(){
        return (self::getPrivateKey() && self::getPublicKey()) ? true : false;
    }

    /**
     * @param string $input 需要编码的字符串
     * @return string
     */
    private static function base64UrlEncode(string $input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode(string $input)
    {
        return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '=', STR_PAD_RIGHT));
    }
}
