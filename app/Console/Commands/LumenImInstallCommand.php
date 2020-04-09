<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\InstallDatabase;

/**
 * LumenImCommand 重写 laravel-swoole 的HttpServerCommand 命令行
 *
 * @package App\Console\Commands
 */
class LumenImInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumenim:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swoole HTTP Server controller.';

    public function handle(){
        $this->info('正在安装数据库...');
        $install = new InstallDatabase();
        $install->init();
        $this->info('数据库已安装完成...');
    }

    private function getTables(){
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        return DB::select('SELECT table_name FROM information_schema.tables WHERE Table_Type="'."BASE TABLE".'" and table_schema="' . $database . '"');
    }
}
