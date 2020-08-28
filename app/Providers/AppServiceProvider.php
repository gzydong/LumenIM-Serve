<?php

namespace App\Providers;

use App\Helpers\Socket\SocketResourceHandle;
use App\Services\ClientManageService;
use App\Services\RoomManageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

use App\Services\UnreadTalkService;
use App\Services\SmsCodeService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 注册用户未读消息服务类
        $this->app->singleton(UnreadTalkService::class, function ($app) {
            return new UnreadTalkService();
        });
        $this->app->alias(UnreadTalkService::class, 'unread.talk');

        // 发送短信验证码服务
        $this->app->singleton(SmsCodeService::class, function ($app) {
            return new SmsCodeService();
        });
        $this->app->alias(SmsCodeService::class, 'sms.code');

        // WebSocket 客户端fd 管理服务
        $this->app->singleton(ClientManageService::class, function ($app) {
            return new ClientManageService();
        });
        $this->app->alias(ClientManageService::class, 'client.manage');

        // 聊天室服务管理
        $this->app->singleton(RoomManageService::class, function ($app) {
            return new RoomManageService();
        });
        $this->app->alias(RoomManageService::class, 'room.manage');
    }

    /**
     * 启动运用服务
     *
     * @return void
     */
    public function boot()
    {
        DB::listen(function ($query) {
            // 查询时间超过一分钟记录日志
            if ($query->time > 1000) {
                Log::alert($query->sql . " >>> 查询时间 time {$query->time}ms");
            }
        });
    }
}
