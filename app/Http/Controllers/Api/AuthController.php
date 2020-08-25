<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Events\UserLoginLogEvent;
use App\Models\User;
use App\Logic\UsersLogic;
use App\Helpers\{JwtAuth, SmsCode};

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
        $fields = ['nickname', 'mobile', 'password', 'sms_code', 'invite_code'];
        if (!$request->filled($fields)) {
            return $this->ajaxParamError();
        }

        $params = $request->only($fields);
        if (!isMobile($params['mobile'])) {
            return $this->ajaxParamError('手机号格式不正确...');
        }

        if ($params['invite_code'] !== '000000') {
            return $this->ajaxParamError('注册邀请码不正确...');
        }

        $sms = new SmsCode();
        if (!$sms->check('user_register', $params['mobile'], $params['sms_code'])) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $isTrue = $usersLogic->register([
            'mobile' => $params['mobile'],
            'password' => $params['password'],
            'nickname' => strip_tags($params['nickname']),
        ]);

        if ($isTrue) {
            $sms->delCode('user_register', $params['mobile']);
        }

        return $isTrue ? $this->ajaxSuccess('账号注册成功...') : $this->ajaxError('账号注册失败,手机号已被其他(她)人使用...');
    }

    /**
     * 账号登录接口
     *
     * @param Request $request
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request, UsersLogic $usersLogic)
    {
        if (!$request->filled(['mobile', 'password'])) {
            return $this->ajaxParamError();
        }

        $data = $request->only(['mobile', 'password']);
        $user = User::where('mobile', $data['mobile'])->first();
        if (!$user) {
            return $this->ajaxReturn(302, '登录账号不存在...');
        }

        if (!$usersLogic->checkAccountPassword($data['password'], $user->password)) {
            return $this->ajaxReturn(305, '登录密码错误...');
        }

        $auth = new JwtAuth();
        $auth->setUid($user->id);
        $auth->encode();

        if (!$token = $auth->getToken()) {
            return $this->ajaxReturn(305, '获取登录状态失败');
        }

        // 记录登录日志
        event(new UserLoginLogEvent($user->id, $request->getClientIp()));

        return $this->ajaxReturn(200, '授权登录成功', [
            // 授权信息
            'authorize' => [
                'access_token' => $token,
                'expires_in' => $auth->getToken(false)->getClaim('exp') - time(),
            ],

            // 用户信息
            'userInfo' => [
                'uid' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'motto' => $user->motto,
                'gender' => $user->gender,
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
        $type = $request->post('type', '');

        if (!in_array($type, SmsCode::CACHE_TAGS)) {
            return $this->ajaxParamError('验证码发送失败...');
        }

        if (!isMobile($mobile)) {
            return $this->ajaxParamError('手机号格式错误...');
        }

        if ($type == 'forget_password') {
            if (!User::where('mobile', $mobile)->value('id')) {
                return $this->ajaxParamError('手机号未被注册使用...');
            }
        } else if ($type == 'change_mobile' || $type == 'user_register') {
            if (User::where('mobile', $mobile)->value('id')) {
                return $this->ajaxParamError('手机号已被他(她)人注册...');
            }
        }

        $sms = new SmsCode();
        [$isTrue, $result] = $sms->send($type, $mobile);

        $data = ['is_debug' => true];
        if ($data['is_debug']) {
            $data['sms_code'] = $result['code'];
        }

        return $this->ajaxSuccess('发送成功', $data);
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
            //return $this->ajaxParamError('密码格式不正确...');
        }

        $sms = new SmsCode();
        if (!$sms->check('forget_password', $mobile, $code)) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $isTrue = $usersLogic->resetPassword($mobile, $password);
        if ($isTrue) {
            $sms->delCode('forget_password', $mobile);
        }

        return $isTrue ? $this->ajaxSuccess('重置密码成功...') : $this->ajaxError('重置密码失败...');
    }
}
