<?php

namespace App\Http\Controllers\Api;

use App\Events\UserLoginLogEvent;
use App\Models\User;
use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Helpers\SmsCode;

/**
 * 接口授权登录控制器
 *
 * Class AuthController
 * @package App\Http\Controllers\Api
 */
class AuthController extends CController
{
    /**
     * 账号注册接口
     *
     * @param Request $request
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request, UsersLogic $usersLogic)
    {
        if (!$request->filled(['mobile', 'password', 'invite_code'])) {
            return $this->ajaxParamError();
        }

        $params = $request->only(['mobile', 'password', 'invite_code']);
        if (!isMobile($params['mobile'])) {
            return $this->ajaxParamError('手机号格式不正确...');
        }

        if ($params['invite_code'] !== '000000') {
            return $this->ajaxParamError('注册邀请码不正确...');
        }

        $isTrue = $usersLogic->register([
            'mobile' => $params['mobile'],
            'password' => $params['password']
        ]);

        return $isTrue ? $this->ajaxSuccess('账号注册成功...') : $this->ajaxError('账号注册失败,手机号已被其他(她)人使用...');
    }

    /**
     * 账号登录接口
     *
     * @param Request $request
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request,UsersLogic $usersLogic)
    {
        if (!$request->filled(['mobile', 'password'])) {
            return $this->ajaxParamError();
        }

        $data = $request->only(['mobile', 'password']);
        $user = User::where('mobile', $data['mobile'])->first();
        if (!$user) {
            return $this->ajaxReturn(302, '登录账号不存在...');
        }

        if (!$usersLogic->checkAccountPassword($data['password'],$user->password)) {
            return $this->ajaxReturn(305, '登录密码错误...');
        }

        $auth = new JwtAuth();
        $auth->setUid($user->id);
        $auth->encode();

        if (!$token = $auth->getToken()) {
            return $this->ajaxReturn(305, '获取登录状态失败');
        }

        event(new UserLoginLogEvent($user->id, $request->getClientIp()));
        return $this->ajaxReturn(200, '授权登录成功', [
            'access_token' => $token,
            'expires_in' => $auth->getToken(false)->getClaim('exp') - time(),
            'userInfo' => [
                'uid' => $user->id,
                'avatar' => $user->avatar,
                'nickname' => $user->nickname
            ]
        ]);
    }

    /**
     * 账号退出登录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        return $this->ajaxReturn(200, '退出成功...', []);
    }

    /**
     * 发送验证码
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerifyCode(Request $request)
    {
        $mobile = $request->post('mobile', '');
        if (!isMobile($mobile)) {
            return $this->ajaxParamError('手机号格式错误...');
        }

        if (!$userInfo = User::where('mobile', $mobile)->first()) {
            return $this->ajaxParamError('手机号未被注册使用...');
        }

        $sms = new SmsCode();
        $sms->send(SmsCode::FORGET_PASSWORD, $mobile);

        $tips = ['tips' => '', 'url' => ''];
        $mobileInfo = getMobileInfo($mobile);
        if ($mobileInfo['type'] == '中国电信') {
            $tips['tips'] = '验证码已发至您的电信189邮箱，请注意查收 ...';
            $tips['url'] = 'https://webmail30.189.cn/w2';
        } else if ($mobileInfo['type'] == '中国移动') {
            $tips['tips'] = '验证码已发至您的139邮箱，请注意查收 ...';
            $tips['url'] = 'https://mail.10086.cn/';
        } else if ($mobileInfo['type'] == '中国联通') {
            $tips['tips'] = '验证码已发至您的联通沃邮箱，请注意查收 ...';
            $tips['url'] = 'https://mail.wo.cn';
        }

        return $this->ajaxSuccess('发送成功', $tips);
    }

    /**
     * 重置用户密码
     *
     * @param Request $request
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgetPassword(Request $request, UsersLogic $usersLogic)
    {
        $mobile = $request->post('mobile', '');
        $code = $request->post('sms_code', '');
        $password = $request->post('password', '');

        if (!isMobile($mobile) || empty($code) || empty($password)) {
            return $this->ajaxParamError();
        }

        if (!isPassword($password)) {
            return $this->ajaxParamError('密码格式不正确...');
        }

        $sms = new SmsCode();
        if (!$sms->check(SmsCode::FORGET_PASSWORD, $mobile, $code)) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $isTrue = $usersLogic->resetPassword($mobile, $password);
        if ($isTrue) {
            $sms->delCode(SmsCode::FORGET_PASSWORD, $mobile);
        }

        return $isTrue ? $this->ajaxSuccess('重置密码成功...') : $this->ajaxError('重置密码失败...');
    }
}
