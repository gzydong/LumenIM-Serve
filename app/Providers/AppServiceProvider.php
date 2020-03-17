<?php
namespace App\Providers;

use App\Helpers\SocketFdUtil;
use Illuminate\Support\ServiceProvider;
use App\Services\Socket\ChatService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('chat:service', function ($app) {
            return new ChatService();
        });

        $this->app->singleton('SocketFdUtil', function ($app) {
            return new SocketFdUtil();
        });
    }
}
