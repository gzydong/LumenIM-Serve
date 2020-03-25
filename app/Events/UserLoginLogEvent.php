<?php
namespace App\Events;

class UserLoginLogEvent
{
    //登录用户
    public $user_id;
    public $login_ip;
    public $created_at;

    /**
     * UserLoginEvent constructor.
     * @param int $user_id
     * @param string $ip
     */
    public function __construct(int $user_id,string $ip)
    {
        $this->user_id = $user_id;
        $this->login_ip = ip2long($ip);
        $this->created_at = date('Y-m-d H:i:s');
    }
}
