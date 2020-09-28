<?php
// @formatter:off

namespace PHPSTORM_META {

    /**
     * PhpStorm Meta file, to provide autocomplete information for PhpStorm
     * Generated on 2020-09-13 16:59:25.
     *
     * @author Barry vd. Heuvel <barryvdh@gmail.com>
     * @see https://github.com/barryvdh/laravel-ide-helper
     */
    override(\app(0), map([
        'App\Services\ClientManageService' => \App\Services\Common\ClientManageService::class,
        'client.manage' => \App\Services\Common\ClientManageService::class,
        'App\Services\JwtAuthService' => \App\Services\Common\JwtAuthService::class,
        'jwt.auth' => \App\Services\Common\JwtAuthService::class,
        'App\Services\RoomManageService' => \App\Services\Common\RoomManageService::class,
        'room.manage' => \App\Services\Common\RoomManageService::class,
        'App\Services\SmsCodeService' => \App\Services\Common\SmsCodeService::class,
        'sms.code' => \App\Services\Common\SmsCodeService::class,
        'App\Services\UnreadTalkService' => \App\Services\Common\UnreadTalkService::class,
        'unread.talk' => \App\Services\Common\UnreadTalkService::class,
        'services' => \App\Services\Service::class,
        'request' => \Illuminate\Http\Request::class
    ]));
}
