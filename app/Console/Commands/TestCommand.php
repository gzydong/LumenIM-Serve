<?php

namespace App\Console\Commands;

use Hashids\Hashids;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;


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
