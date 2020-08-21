<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class SmsCode
{

    //  验证码用途渠道
    const CACHE_TAGS = [
        'forget_password',
        'change_mobile',
        'user_register'
    ];

    private function getKey(string $type, string $mobile)
    {
        return "sms_code:{$type}:{$mobile}";
    }

    /**
     * 检测验证码是否正确
     *
     * @param string $type 发送类型
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @return bool
     */
    public function check(string $type, string $mobile, string $code)
    {
        $sms_code = Redis::get($this->getKey($type, $mobile));
        if (!$sms_code) {
            return false;
        }

        return $sms_code == $code;
    }

    /**
     * 发送验证码
     *
     * @param string $type 类型
     * @param string $mobile 手机号
     */
    public function send(string $type, string $mobile)
    {
        if (!in_array($type, self::CACHE_TAGS)) {
            return false;
        }

        $key = $this->getKey($type, $mobile);
        if (!$sms_code = $this->getCode($key)) {
            $sms_code = random(6, 'number');
        }

        $this->setCode($key, $sms_code);

        // 后期采用异步发送

        return [true, ['type' => $type, 'code' => $sms_code]];
    }

    /**
     * 获取缓存的验证码
     *
     * @param string $key
     * @return mixed
     */
    public function getCode(string $key)
    {
        return Redis::get($key);
    }

    /**
     * 设置验证码缓存
     *
     * @param string $key 缓存key
     * @param string $sms_code 验证码
     * @param float|int $exp 过期时间（默认15分钟）
     * @return mixed
     */
    public function setCode(string $key, string $sms_code, $exp = 60 * 15)
    {
        return Redis::setex($key, $exp, $sms_code);
    }

    /**
     * 删除验证码缓存
     *
     * @param string $type 类型
     * @param string $mobile 手机号
     * @return mixed
     */
    public function delCode(string $type, string $mobile)
    {
        return Redis::del($this->getKey($type, $mobile));
    }
}
