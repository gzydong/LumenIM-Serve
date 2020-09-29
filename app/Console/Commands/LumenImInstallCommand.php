<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\InstallDatabase;

/**
 *  项目安装命令
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
    protected $signature = 'lumen-im:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '项目初始化安装命令';

    public function handle()
    {
        $install = new InstallDatabase($this);
        $install->init();
    }
}
