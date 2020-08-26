<?php

namespace App\Services;

/**
 * 客户端Fd管理服务
 *
 * Class ClientBindService
 * @package App\Services
 */
class ClientManageService
{

    /**
     * 判单用户当前是否在线
     *
     * @param int $user_id 用户ID
     * @return bool
     */
    public function isOnline(int $user_id)
    {
        return true;
    }
}
