<?php
namespace App\Providers;

use App\Helpers\Socket\SocketResourceHandle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 注册Socket资源处理类
        $this->app->singleton(SocketResourceHandle::class, function ($app) {
            return new SocketResourceHandle();
        });
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
            if($query->time > 1000){
                Log::alert($query->sql." >>> 查询时间 time {$query->time}ms");
            }
        });
    }
}
