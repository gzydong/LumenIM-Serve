<?php

namespace App\Http\Controllers\Api;

use App\Services\UserService;
use Illuminate\Http\Request;
use App\Events\UserLoginLogEvent;
use App\Models\User;

/**
 * 接口授权登录控制器
 *
 * Class AuthController
 * @package App\Http\Controllers\Api
 */
class AuthController extends CController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UserService
     */
    protected $userService;

    public function __construct(Request $request, UserService $userService)
    {
        $this->request = $request;
        $this->userService = $userService;
    }

    /**
     * 注册接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register()
    {
        $fields = ['nickname', 'mobile', 'password', 'sms_code'];
        if (!$this->request->filled($fields)) {
            return $this->ajaxParamError();
        }

        $params = $this->request->only($fields);
        if (!check_mobile($params['mobile'])) {
            return $this->ajaxParamError('手机号格式不正确...');
        }

        if (!app('sms.code')->check('user_register', $params['mobile'], $params['sms_code'])) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $isTrue = $this->userService->register([
            'mobile' => $params['mobile'],
            'password' => $params['password'],
            'nickname' => strip_tags($params['nickname']),
        ]);

        if ($isTrue) {
            app('sms.code')->delCode('user_register', $params['mobile']);
        }

        return $isTrue ? $this->ajaxSuccess('账号注册成功...') : $this->ajaxError('账号注册失败,手机号已被其他(她)人使用...');
    }

    /**
     * 授权登录接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        if (!$this->request->filled(['mobile', 'password'])) {
            return $this->ajaxParamError();
        }

        $data = $this->request->only(['mobile', 'password']);
        $user = User::where('mobile', $data['mobile'])->first();
        if (!$user) {
            return $this->ajaxReturn(302, '登录账号不存在...');
        }

        if (!$this->userService->checkPassword($data['password'], $user->password)) {
            return $this->ajaxReturn(305, '登录密码错误...');
        }

        $jwtConfig = config('config.jwt');
        $jwtObject = app('jwt.auth')->jwtObject();
        $jwtObject->setAlg($jwtConfig['algo']); // 加密方式
        $jwtObject->setAud('user'); // 用户
        $jwtObject->setExp(time() + $jwtConfig['ttl']); //  jwt的过期时间，这个过期时间必须要大于签发时间
        $jwtObject->setIat(time()); // 发布时间
        $jwtObject->setIss('lumen-im'); // 发行人
        $jwtObject->setJti(md5(time() . mt_rand(10000, 99999) . uniqid())); // jwt id 用于标识该jwt
        $jwtObject->setNbf(time()); // 定义在什么时间之前，该jwt都是不可用的.
        $jwtObject->setSub('Authorized login'); // 主题
        $jwtObject->setData([
            'uid' => $user->id
        ]);

        if (!$token = $jwtObject->token()) {
            return $this->ajaxReturn(305, '获取登录状态失败');
        }

        // 记录登录日志
        event(new UserLoginLogEvent($user->id, $this->request->getClientIp()));

        return $this->ajaxReturn(200, '授权登录成功', [
            // 授权信息
            'authorize' => [
                'access_token' => $token,
                'expires_in' => $jwtObject->getExp() - time(),
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
     * 退出登录
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function logout()
    {
        $token = parse_token();

        app('jwt.auth')->joinBlackList($token, app('jwt.auth')->decode($token)->getExp() - time());

        return $this->ajaxReturn(200, '退出成功...', []);
    }

    /**
     * 发送验证码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerifyCode()
    {
        $mobile = $this->request->post('mobile', '');
        $type = $this->request->post('type', '');

        if (!app('sms.code')->isUsages($type)) {
            return $this->ajaxParamError('验证码发送失败...');
        }

        if (!check_mobile($mobile)) {
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

        $data = ['is_debug' => true];
        [$isTrue, $result] = app('sms.code')->send($type, $mobile);
        if ($isTrue) {
            $data['sms_code'] = $result['data']['code'];
        } else {
            // ... 处理发送失败逻辑，当前默认发送成功
        }

        return $this->ajaxSuccess('发送成功', $data);
    }

    /**
     * 重置用户密码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgetPassword()
    {
        $mobile = $this->request->post('mobile', '');
        $code = $this->request->post('sms_code', '');
        $password = $this->request->post('password', '');

        if (!check_mobile($mobile) || empty($code) || empty($password)) {
            return $this->ajaxParamError();
        }

        if (!check_password($password)) {
            //return $this->ajaxParamError('密码格式不正确...');
        }

        if (!app('sms.code')->check('forget_password', $mobile, $code)) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $isTrue = $this->userService->resetPassword($mobile, $password);
        if ($isTrue) {
            app('sms.code')->delCode('forget_password', $mobile);
        }

        return $isTrue ? $this->ajaxSuccess('重置密码成功...') : $this->ajaxError('重置密码失败...');
    }
}
