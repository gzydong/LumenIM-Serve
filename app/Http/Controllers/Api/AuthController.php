<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Rsa;
use App\Models\User;
use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        if (!$request->filled(['mobile', 'password'])) {
            return $this->ajaxParamError();
        }

        $data = $request->only(['mobile', 'password']);
        $user = User::where('mobile', $data['mobile'])->first();
        if (!$user) {
            return $this->ajaxReturn(302, '登录账号不存在...');
        }

        if (!Hash::check($data['password'], $user->password)) {
            return $this->ajaxReturn(305, '登录密码错误...');
        }

        if (!$token = $this->guard()->login($user)) {
            return $this->ajaxReturn(305, '获取登录状态失败');
        }

        return $this->ajaxReturn(200, '授权登录成功', [
            'access_token' => $token,
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'userInfo' => [
                'uid' => $user->id,
                'avatar' => $user->avatarurl,
                'nickname' => $user->nickname,
                'sign'=>Rsa::encrypt($user->id)
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
        try {
            $this->guard()->logout(true);
        } catch (\Exception $e) {
        }
        return $this->ajaxSuccess('退出成功');
    }

    /**
     * 刷新授权 access_token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken()
    {
        if (!$this->guard()->getToken()) {
            return $this->ajaxReturn(401, 'The token could not be parsed from the request');
        }

        $expires_in = $this->guard()->factory()->getTTL() * 60;
        try {
            $token = $this->guard()->refresh();
        } catch (\Exception $e) {
            return $this->ajaxReturn(305, $e->getMessage());
        }

        if ($token) {
            return $this->ajaxSuccess('Refresh success', [
                'access_token' => $token,
                'expires_in' => $expires_in
            ]);
        }

        return $this->ajaxError(305, 'Token has expired and can no longer be refreshed');
    }

    /**
     * 发送验证码
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerifyCode(Request $request){
        $mobile = $request->post('mobile','18798276809');
        if(!isMobile($mobile)){
            return $this->ajaxParamError('手机号格式错误...');
        }

        $userInfo = User::where('mobile',$mobile)->first();
        if(!$userInfo){
            return $this->ajaxParamError('手机号未被注册使用...');
        }

        $tips = ['tips'=>'','url'=>''];
        $email = '';
        $mobileInfo = getMobileInfo($mobile);
        if($mobileInfo['type'] == '中国电信'){
            $tips['tips'] = '验证码已发至您的电信189邮箱，请注意查收 ...';
            $tips['url'] = 'https://webmail30.189.cn/w2';
            $email = $mobile.'@189.com';
        }else if($mobileInfo['type'] == '中国移动'){
            $tips['tips'] = '验证码已发至您的139邮箱，请注意查收 ...';
            $tips['url'] = 'https://mail.10086.cn/';
            $email = $mobile.'@139.com';
        }else if($mobileInfo['type'] == '中国联通'){
            $tips['tips'] = '验证码已发至您的联通沃邮箱，请注意查收 ...';
            $tips['url'] = 'https://mail.wo.cn';
            $email = $mobile.'@wo.cn';
        }

        if(!$sms_code = Redis::get("str:forget_password:{$mobile}")){
            $sms_code = random(6,'number');
        }

        Redis::setex("str:forget_password:{$mobile}", 60 * 15, $sms_code);
        Mail::send('emails.verify-code',['service_name'=>'重置密码','sms_code'=>$sms_code,'domain'=>'http://47.105.180.123:83/forget'], function($message) use($email){
            $message->to($email)->subject('On-line IM 重置密码(验证码)');
        });

        return $this->ajaxSuccess('发送成功',$tips);
    }

    /**
     * 重置用户密码
     *
     * @param Request $request
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgetPassword(Request $request, UsersLogic $usersLogic){
        $mobile = $request->post('mobile','');
        $code = $request->post('sms_code','');
        $password = $request->post('password','');

        if(!isMobile($mobile) || empty($code) || empty($password)){
            return $this->ajaxParamError();
        }

        if(!isPassword($password)){
            return $this->ajaxParamError('密码格式不正确...');
        }

        $sms_code = Redis::get("str:forget_password:{$mobile}");
        if(!$sms_code || $sms_code != $code){
            return $this->ajaxParamError('验证码填写错误...');
        }

        $isTrue = $usersLogic->resetPassword($mobile,$password);
        if($isTrue){
            Redis::del("str:forget_password:{$mobile}");
        }

        return $isTrue ? $this->ajaxSuccess('重置密码成功...') : $this->ajaxError('重置密码失败...');
    }
}
