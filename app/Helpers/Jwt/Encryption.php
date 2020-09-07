<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2020/9/7
 * Time: 16:54
 */

namespace App\Helpers\Jwt;


class Encryption
{
    public static function base64UrlEncode($content)
    {
        return str_replace('=', '', strtr(base64_encode($content), '+/', '-_'));
    }

    public static function base64UrlDecode($content)
    {
        $remainder = strlen($content) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $content .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($content, '-_', '+/'));
    }
}
