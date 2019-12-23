<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\FileSplitUpload;
use Illuminate\Support\Facades\Storage;


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
    protected $signature = 'lumenim:clear-tmp-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除拆分上传的临时文件';

    public function handle(){
        FileSplitUpload::select(['id','save_dir'])->where('is_delete',0)->where('upload_at','<',time())->chunk(200, function ($rows) {
            $ids = [];
            try{
                foreach ($rows as $row){
                    $ids[] = $row->id;
                    Storage::disk('uploads')->delete($row->save_dir);
                }
            }catch (\Exception $e){}


            FileSplitUpload::whereIn('id',$ids)->update(['is_delete'=>1]);
        });
    }
}
