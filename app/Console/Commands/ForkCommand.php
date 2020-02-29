<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SwooleTW\Http\Server\PidManager;
use Illuminate\Support\Arr;
class ForkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumenim:fork';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //当子进程退出时，会触发该函数,当前子进程数-1
        //配合pcntl_signal使用
        declare (ticks=1);

        //当子进程退出时，会触发该函数,当前子进程数-1
        pcntl_signal(SIGCHLD, function ($signo) {
            echo '信号: '.$signo.'\n';
        });

        $this->fork(4);

        // 等待子进程执行结束
        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
            echo "Child $status completed\n";
        }
    }


    /**
     * 创建多进程
     * @param $num
     */
    public function fork($num = 1)
    {
        echo "进程创建中({$num})...\n";
        for ($i = 0; $i < $num; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('进程创建失败');
            } elseif ($pid) {
            } else {
                $cid = posix_getpid();
                $ppid = posix_getppid();
                echo "fork the {$i} th child, pid: $cid  $ppid \n";

                $this->run2($cid);
                exit;
            }
        }
    }


    public function run2($pid)
    {

        while (true) {
            sleep(3);
        }
    }
}
