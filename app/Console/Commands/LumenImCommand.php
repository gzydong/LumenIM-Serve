<?php
namespace App\Console\Commands;

use SwooleTW\Http\Commands\HttpServerCommand;
use App\Facades\WebSocketHelper;

/**
 * LumenImCommand 重写 laravel-swoole 的HttpServerCommand 命令行
 *
 * @package App\Console\Commands
 */
class LumenImCommand extends HttpServerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumenim:swoole {action : start|stop|restart|reload|infos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swoole HTTP Server controller.';

    /**
     * Run swoole_http_server.
     */
    protected function start()
    {
        if ($this->isRunning()) {
            $this->error('Failed! swoole_http_server process is already running.');
            return;
        }

        //清除redis 缓存
        WebSocketHelper::clearRedisCache();

        parent::start();
    }
}
