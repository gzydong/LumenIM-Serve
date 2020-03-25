<?php
namespace App\Listeners;

use App\Events\UserLoginLogEvent;
use Illuminate\Support\Facades\DB;

class UserLoginLogListener
{
    /**
     * 处理事件
     *
     * @param UserLoginLogEvent $event
     */
    public function handle(UserLoginLogEvent $event)
    {
        DB::table('user_login_log')->insert([
            'user_id'=>$event->user_id,
            'ip'=>$event->login_ip,
            'created_at'=>$event->created_at,
        ]);
    }
}
