<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Helpers\RsaMeans;

/**
 * 接口授权登录控制器
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
    public function register(Request $request,UsersLogic $usersLogic){
        if(!$request->filled(['mobile','password','invite_code'])){
            return $this->ajaxParamError();
        }

        $params = $request->only(['mobile','password','invite_code']);
        if(!isMobile($params['mobile'])){
            return $this->ajaxParamError('手机号格式不正确...');
        }

        if($params['invite_code'] == '000000'){
            //return $this->ajaxParamError('注册邀请码不正确...');
        }

        $isTrue = $usersLogic->register([
            'mobile'  =>$params['mobile'],
            'password'=>$params['password']
        ]);

        return $isTrue ? $this->ajaxSuccess('账号注册成功...') : $this->ajaxError('账号注册失败,手机号已被其他(她)人使用...');
    }

    /**
     * 账号登录接口
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
        if(!$request->filled(['mobile','password'])){
            return $this->ajaxParamError();
        }

        $data = $request->only(['mobile','password']);
        $user = User::where('mobile', $data['mobile'])->first();
        if(!$user){
            return $this->ajaxReturn(302,'登录账号不存在...');
        }

        if(!Hash::check($data['password'], $user->password)){
            return $this->ajaxReturn(305,'登录密码错误...');
        }

        if (!$token = Auth::login($user)) {
            return $this->ajaxReturn(305, '获取登录状态失败');
        }

        return $this->ajaxReturn(200, '授权登录成功', [
            'access_token' => $token,
            'sid'=>RsaMeans::encrypt($user->id)
        ]);
    }

    /**
     * 用户退出登录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::invalidate(true);
        return $this->ajaxSuccess('退出成功');
    }

    /**
     * 刷新授权 access_token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken()
    {
        if (!$token = Auth::refresh(true, true)) {
            return $this->ajaxReturn(200, '刷新access_token 成功', ['access_token' => $token]);
        } else {
            return $this->ajaxError('刷新授权 access_token 失败');
        }
    }
}
