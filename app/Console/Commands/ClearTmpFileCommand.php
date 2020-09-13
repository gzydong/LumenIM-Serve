<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FileSplitUpload;
use Illuminate\Support\Facades\Storage;

set_time_limit(0);

/**
 * LumenImCommand 重写 laravel-swoole 的HttpServerCommand 命令行
 *
 * @package App\Console\Commands
 */
class ClearTmpFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumen-im:clear-tmp-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除拆分上传的临时文件';

    public function handle()
    {
        FileSplitUpload::select(['id', 'save_dir', 'hash_name'])->where('file_type', 1)->where('is_delete', 0)->where('upload_at', '<', time() - 60 * 60 * 6)->chunk(200, function ($rows) {
            $hash_name = [];
            try {
                foreach ($rows as $row) {
                    $hash_name[] = $row->hash_name;
                    $dir = pathinfo($row->save_dir, PATHINFO_DIRNAME);

                    if (Storage::disk('uploads')->exists($dir)) {
                        Storage::disk('uploads')->deleteDirectory($dir);
                    }
                }

                FileSplitUpload::whereIn('hash_name', $hash_name)->update(['is_delete' => 1]);
            } catch (\Exception $e) {
            }
        });
    }
}
