<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Helpers\WebSocketHelper;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('websocket.helper', function ($app) {
            return new WebSocketHelper();
        });

        $this->app->singleton('chat.service', function ($app) {
            return new ChatService();
        });
    }
}
