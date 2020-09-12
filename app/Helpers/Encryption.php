<?php

namespace App\Helpers;

/**
 * Class EncryptionHelper
 * @package App\Helpers
 */
class Encryption
{
    /**
     * Url 加密
     * @param $content
     * @return mixed
     */
    public static function base64UrlEncode($content)
    {
        return str_replace('=', '', strtr(base64_encode($content), '+/', '-_'));
    }

    /**
     * Url 解密
     * @param $content
     * @return mixed
     */
    public static function base64UrlDecode($content)
    {
        $remainder = strlen($content) % 4;
        if ($remainder) {
            $content .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($content, '-_', '+/'));
    }
}
