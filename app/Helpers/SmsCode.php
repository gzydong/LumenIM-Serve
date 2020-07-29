<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class SmsCode
{

    const FORGET_PASSWORD = 'forget_password';
    const CHANGE_MOBILE = 'change_mobile';
    const CHANGE_REGISTER = 'user_register';

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
        if(!$sms_code){
            return false;
        }

        return $sms_code == $code;
    }

    /**
     * 发送验证码(可切换为短信方式)
     *
     * @param string $type 类型
     * @param string $mobile 手机号
     */
    public function send(string $type, string $mobile)
    {
        $email = '';
        $mobileInfo = MobileInfo::info($mobile);

        if ($mobileInfo['company'] == '电信') {
            $email = $mobile . '@189.com';
        } else if ($mobileInfo['company'] == '移动') {
            $email = $mobile . '@139.com';
        } else if ($mobileInfo['company'] == '联通') {
            $email = $mobile . '@wo.cn';
        }

        $key = $this->getKey($type, $mobile);
        if (!$sms_code = $this->getCode($key)) {
            $sms_code = random(6, 'number');
        }

        $title = '';
        if($type === SmsCode::FORGET_PASSWORD){
            $title = '重置密码';
        }else if($type === SmsCode::CHANGE_MOBILE){
            $title = '换绑手机';
        }

        $this->setCode($key,$sms_code);

        Mail::send('emails.verify-code', ['service_name' => $title, 'sms_code' => $sms_code, 'domain' => 'http://47.105.180.123:83'], function ($message) use ($email,$title) {
            $message->to($email)->subject("On-line IM {$title}(验证码)");
        });
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
     * @param float|int $exp 过期时间
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
