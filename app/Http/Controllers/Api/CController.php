<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\JsonTrait;
use Illuminate\Http\Request;

class CController extends Controller
{
    use JsonTrait;

    /**
     * 重写请求验证失败响应信息
     *
     * {@inheritdoc}
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if (isset(static::$responseBuilder)) {
            return call_user_func(static::$responseBuilder, $request, $errors);
        }

        return $this->ajaxParamError(array_shift($errors)[0]);
    }

    /**
     * 获取用户ID
     *
     * @return int 用户ID
     */
    protected function uid()
    {
        $token = parse_token();
        try {
            $jwtObject = app('jwt.auth')->decode($token);
            if ($jwtObject->getStatus() != 1) {
                return 0;
            }
        } catch (\Exception $e) {
            return 0;
        }

        return $jwtObject->getData()['uid'] ?? 0;
    }
}
