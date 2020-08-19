<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

set_time_limit(0);

/**
 * 测试命令行
 *
 * @package App\Console\Commands
 */
class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumen-im:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试命令行';

    public function handle()
    {

    }
}
