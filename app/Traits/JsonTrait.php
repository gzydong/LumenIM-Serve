<?php

namespace App\Traits;

/**
 * Trait JsonTrait json 数据返回
 *
 * @package App\Traits
 */
trait JsonTrait
{
    /**
     * 返回 json 数据信息
     *
     * @param int $code 状态码
     * @param string $msg 提示信息
     * @param array $data 响应数据
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxReturn(int $code, $msg, $data = [])
    {
        return response()->json(compact('code', 'msg', 'data'));
    }

    /**
     * 返回成功时的数据信息
     *
     * @param string $msg 提示信息
     * @param array $data 响应数据
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxSuccess(string $msg, $data = [])
    {
        return $this->ajaxReturn(200, $msg, $data);
    }

    /**
     * 返回失败时的数据
     *
     * @param string $msg 提示信息
     * @param array $data 响应数据
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxError(string $msg, $data = [])
    {
        return $this->ajaxReturn(305, $msg, $data);
    }

    /**
     * 请求参数验证失败提示信息
     *
     * @param string $msg 提示信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ajaxParamError($msg = '请求参数错误')
    {
        return $this->ajaxReturn(301, $msg, []);
    }
}
