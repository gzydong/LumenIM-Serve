<?php

namespace App\Console;

use App\Console\Commands\ForeverArticleCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Console\Commands\KeyGenerateCommand;
use App\Console\Commands\ClearTmpFileCommand;
use App\Console\Commands\LumenImInstallCommand;
use App\Console\Commands\TestCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        KeyGenerateCommand::class,
        ClearTmpFileCommand::class,
        LumenImInstallCommand::class,
        ForeverArticleCommand::class,
        TestCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //每天凌晨3点执行清除上传临时文件
        $schedule->command('lumen-im:clear-tmp-file')
            ->cron('0 3 * * *')
            ->before(function () {
                Log::info('lumen-im:clear-tmp-file ------- start:' . time());
            })->after(function () {
                Log::info('lumen-im:clear-tmp-file ------- end:' . time());
            })->runInBackground();

        //每天凌晨3点30分执行清除笔记回收站及附件回收站信息
        $schedule->command('lumen-im:forever-article')
            ->cron('30 3 * * *')
            ->before(function () {
                Log::info('lumen-im:forever-article ------- start:' . time());
            })->after(function () {
                Log::info('lumen-im:forever-article ------- end:' . time());
            })->runInBackground();
    }
}
