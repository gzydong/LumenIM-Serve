<?php
namespace App\Console\Commands;

use SwooleTW\Http\Commands\HttpServerCommand;
use App\Facades\SocketResourceHandle;

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
    protected $signature = 'lumen-im:swoole {action : start|stop|restart|reload|infos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swoole HTTP Server controller.';

    protected $logo = <<<LOGO
   ____              _ _               _____ __  __     _____                          
  / __ \            | (_)             |_   _|  \/  |   /____|                         
 | |  | |_ __ ______| |_ _ __   ___     | | | \  / |  | (___   ___ _ ____   _____ _ __ 
 | |  | | '_ \______| | | '_ \ / _ \    | | | |\/| |   \___ \ / _ \ '__\ \ / / _ \ '__|
 | |__| | | | |     | | | | | |  __/   _| |_| |  | |   ____) |  __/ |   \ V /  __/ |   
  \____/|_| |_|     |_|_|_| |_|\___|  |_____|_|  |_|  |_____/ \___|_|    \_/ \___|_|   
                                                                                     
LOGO;

    /**
     * Run swoole_http_server.
     */
    protected function start()
    {
        if ($this->isRunning()) {
            $this->error('Failed! swoole_http_server process is already running.');
            return;
        }

        SocketResourceHandle::clearRedisCache();

        $this->info($this->logo);

        parent::start();
    }
}
