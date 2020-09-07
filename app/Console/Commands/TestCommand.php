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
        $id = Redis::get('last_login_id') | 518;
        $rows = DB::connection('mysql2')->table('user_login_log')
            ->leftJoin('users', 'user_login_log.user_id', '=', 'users.id')
            ->where('user_login_log.id', '>', $id)
            ->get([
                'user_login_log.id', 'user_login_log.ip', 'user_login_log.created_at', 'users.mobile'
            ])->toArray();

        if ($rows) {
            Redis::set('last_login_id', end($rows)->id);
            Mail::send('emails.login', [
                'rows' => $rows
            ], function ($message) {
                $message->to('837215079@qq.com')->subject("登录通知");
            });
        }
    }
}
