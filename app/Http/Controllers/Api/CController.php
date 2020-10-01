<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\JsonTrait;

class CController extends Controller
{
    use JsonTrait;

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
