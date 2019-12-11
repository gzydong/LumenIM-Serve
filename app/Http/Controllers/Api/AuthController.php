<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\RsaMeans;
use App\Facades\WebSocketHelper;

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

        if ($params['invite_code'] == '000000') {
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

        //判断系统是否在其他地方登录，若存在则将强制下线
        if ($fds = WebSocketHelper::getUserFds($user->id)) {
            try{
                WebSocketHelper::disconnect($fds);
            }catch (\Exception $e){}

        }

        return $this->ajaxReturn(200, '授权登录成功', [
            'access_token' => $token,
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'sid' => RsaMeans::encrypt($user->id),
            'userInfo' => [
                'uid' => $user->id,
                'avatar' => $user->avatarurl,
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
}
