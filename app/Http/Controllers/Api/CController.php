<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class CController extends Controller
{
    /**
     * 获取用户ID
     *
     * @return int
     */
    protected function uid()
    {
        $token = parse_token();
        try{
            $jwtObject = app('jwt.auth')->decode($token);
            if($jwtObject->getStatus() != 1){
                return 0;
            }
        }catch (\Exception $e){
            return 0;
        }

        return $jwtObject->getData()['uid']??0;
    }

    /**
     * 获取当前用户信息
     *
     * @param bool $isArray
     * @return array
     */
    protected function getUser($isArray = false)
    {
        $uid = $this->uid();
        if ($uid == 0) {
            return [];
        }

        if (!$isArray) {
            return User::where('id', $uid)->first();
        }

        return User::where('id', $uid)->first()->toArray();
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
}
