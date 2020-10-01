<?php

namespace App\Events;

/**
 * 登录事件
 *
 * Class UserLoginLogEvent
 * @package App\Events
 */
class UserLoginLogEvent
{
    /**
     * 登录用户ID
     * @var int
     */
    public $user_id;

    /**
     * 登录IP
     *
     * @var string
     */
    public $login_ip;

    /**
     * 登录时间
     *
     * @var false|string
     */
    public $created_at;

    /**
     * UserLoginEvent constructor.
     * @param int $user_id
     * @param string $ip
     */
    public function __construct(int $user_id, string $ip)
    {
        $this->user_id = $user_id;
        $this->login_ip = $ip;
        $this->created_at = date('Y-m-d H:i:s');
    }
}
