<?php
namespace App\Console\Commands;

use SwooleTW\Http\Commands\HttpServerCommand;
use App\Facades\WebSocketHelper;
use Illuminate\Support\Arr;
use SwooleTW\Http\Server\Manager;
use SwooleTW\Http\Server\Facades\Server;

class LumenSwooleCommand extends HttpServerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumen:swoole {action : start|stop|restart|reload|infos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Run swoole_http_server.
     */
    protected function start()
    {
        if ($this->isRunning()) {
            $this->error('Failed! swoole_http_server process is already running.');
            return;
        }

        WebSocketHelper::clearRedisCache();
        parent::start();
    }
}
