<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use Illuminate\Support\Facades\Auth;
class CController extends Controller
{

    /**
     * 获取 API 的守卫
     *
     * @return mixed
     */
    protected function guard()
    {
        return Auth::guard('api');
    }

    /**
     * 返回ajax 数据
     * @param $code
     * @param $msg
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxReturn($code, $msg, $data = [])
    {
        return response()->json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 返回成功时的数据
     *
     * @param $msg
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxSuccess($msg, $data = [])
    {
        return $this->ajaxReturn(200, $msg, $data);
    }

    /**
     * 返回失败时的数据
     *
     * @param $msg
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxError($msg, $data = [])
    {
        return $this->ajaxReturn(305, $msg, $data);
    }

    /**
     * 请求参数错误提示
     * @param string $msg
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxParamError($msg = '请求参数错误')
    {
        return $this->ajaxReturn(301, $msg, []);
    }

    /**
     * 判断用户是否授权
     *
     * @return bool
     */
    protected function checkLogin()
    {
        return $this->guard()->user() ? true : false;
    }

    /**
     * 获取用户ID
     *
     * @return int
     */
    protected function uid()
    {
        $user = $this->guard()->user();
        return $user ? $user->id : 0;
    }

    /**
     * 获取当前用户信息
     *
     * @param bool $isArray
     * @return array
     */
    protected function getUser($isArray = false)
    {
        if (!$user = $this->guard()->user()) {
            return [];
        }

        return $isArray ? $user->toArray() : $user;
    }
}