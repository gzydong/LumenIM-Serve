<?php

namespace App\Providers;

use App\Services\Service;
use Illuminate\Support\ServiceProvider;
use App\Services\Common\ClientManageService;
use App\Services\Common\JwtAuthService;
use App\Services\Common\RoomManageService;
use App\Services\Common\UnreadTalkService;
use App\Services\Common\SmsCodeService;

class AppServiceProvider extends ServiceProvider
{

    /**
     * 设定所有的单例模式容器绑定的对应关系
     *
     * @var array
     */
    public $singletons = [
        // 注册用户未读消息服务类
        UnreadTalkService::class => UnreadTalkService::class,

        // 发送短信验证码服务
        SmsCodeService::class => SmsCodeService::class,

        // WebSocket 客户端fd 管理服务
        ClientManageService::class => ClientManageService::class,

        // 聊天室服务管理
        RoomManageService::class => RoomManageService::class,

        // jwt 授权服务
        JwtAuthService::class => JwtAuthService::class,

        // App 服务基类
        Service::class => Service::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->alias(Service::class, 'services');
        $this->app->alias(UnreadTalkService::class, 'unread.talk');
        $this->app->alias(SmsCodeService::class, 'sms.code');
        $this->app->alias(ClientManageService::class, 'client.manage');
        $this->app->alias(RoomManageService::class, 'room.manage');
        $this->app->alias(JwtAuthService::class, 'jwt.auth');
    }

    /**
     * 启动运用服务
     *
     * @return void
     */
    public function boot()
    {

    }
}
