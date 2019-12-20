<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\FileSplitUpload;
use App\Models\UsersChatFiles;

/**
 * 上传文件控制器
 *
 * Class UploadController
 * @package App\Http\Controllers\Web
 */
class UploadController extends CController
{

    /**
     * 图片上传接口
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function img(Request $request){
        $file = $request->file('img');
        if (!$file->isValid()) {
            return $this->ajaxParamError('请求参数错误');
        }

        //图片格式验证
        if (!in_array($file->getClientOriginalExtension(), ['jpg', 'png', 'jpeg', 'gif','webp'])) {
            return $this->ajaxParamError('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        //保存图片
        if(!$path = Storage::disk('uploads')->put('chatimg/'.date('Ymd'), $file)){
            return $this->ajaxError('图片上传失败');
        }

        $avatar = config('config.upload.upload_domain','').'/'.$path;
        return $this->ajaxSuccess('图片上传成功...',['img'=>$avatar]);
    }

    /**
     * 文件上传接口
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function file(Request $request){
        $file = $request->file('files');
        if (!$file->isValid()) {
            return $this->ajaxParamError('请求参数错误');
        }

        //图片格式验证
        if (!in_array($file->getClientOriginalExtension(), ['txt', 'zip', 'rar', 'log','html','json','doc'])) {
            return $this->ajaxParamError('文件格式错误，目前仅支持txt、zip、rar、log、html、json、doc');
        }

        //保存文件
        $path = Storage::disk('files')->put(date('Ymd'), $file);
        return $path ? $this->ajaxSuccess('文件上传成功...',['img'=>"/storage/{$path}"]) : $this->ajaxError('文件上传失败...');
    }

    /**
     * 图片文件流上传接口
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileStream(Request $request){
        $fileStream = $request->post('fileStream','');

        $data = base64_decode(str_replace(['data:image/png;base64,',' '],['','+'],$fileStream));
        $path = 'avatar/'.date('Ymd').'/'.uniqid() . date('His') . '.png';
        if(!Storage::disk('uploads')->put($path, $data)){
            return $this->ajaxError('文件保存失败');
        }

        $avatar = config('config.upload.upload_domain','').'/'.$path;
        return $this->ajaxSuccess('文件上传成功...',['avatar'=>$avatar]);
    }

    /**
     * 文件分区上传
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileSubareaUpload(Request $request){
        $file = $request->file('file');
        $params = ['name','hash','index','ext','size','index','total_index'];
        if(!$request->filled($params) || !$file){
            return $this->ajaxParamError();
        }

        $info = $request->only($params);
        $save_dir = "tmp/{$info['hash']}";
        $save_file_name = "{$info['hash']}_{$info['index']}_{$info['ext']}.tmp";
        if(!$path = Storage::disk('uploads')->putFileAs($save_dir, $file,$save_file_name)){
            return $this->ajaxError('上传文件失败...');
        }

        DB::table('file_split_upload')->insertGetId([
            'user_id'=>$this->uid(),
            'hash_name'=>$info['hash'],
            'original_name'=>$info['name'],
            'index'=>$info['index'],
            'total_index'=>$info['total_index'],
            'save_dir'=>"{$save_dir}/{$save_file_name}",
            'file_ext'=>$info['ext'],
            'file_size'=>$info['size'],
            'upload_at'=>time(),
        ]);

        return $this->ajaxSuccess('上传文件成功...');
    }

    /**
     * 分区文件请求合并操作
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileMerge(Request $request){
        $hash_name = $request->post('hash_name','15768256985459719064252992027');

        $files = FileSplitUpload::where('user_id',$this->uid())->where('hash_name',$hash_name)->orderBy('index','asc')->get(['index','original_name','save_dir','is_delete','total_index','file_size','file_ext'])->toArray();
        if(!$files){
            return $this->ajaxError('文件信息不存在...');
        }

        $count = count($files);
        if($files[0]['total_index'] != $count){
            return $this->ajaxReturn('文件还在上传中...');
        }

        $dir = base_path('uploads');
        $fileMerge =  "tmp/{$hash_name}/{$files[0]['original_name']}";
        $file_size = 0;
        foreach ($files as $file){
            file_put_contents($dir.'/'.$fileMerge, file_get_contents($dir.'/'.$file['save_dir']), FILE_APPEND);
            $file_size += $file['file_size'];
        }

        $save_dir = "user-file/".date('Ymd').'/'.getSaveFile('zip');
        if(Storage::disk('uploads')->move($fileMerge,$save_dir)){
            $res = UsersChatFiles::create([
                'user_id'=>$this->uid(),
                'file_type'=>UsersChatFiles::getFileType($files[0]['file_ext']),
                'file_suffix'=>$files[0]['file_ext'],
                'file_size'=>$file_size,
                'save_dir'=>$save_dir,
                'original_name'=>$files[0]['original_name'],
                'created_at'=>date('Y-m-d H:i:s')
            ]);

            dd($res);

            echo '文件合并成功...';
        }
    }


    public function download(){
        return Storage::disk('uploads')->download('user-file/20191220/im-5dfc87baad1b0-5dfc87baad1b3.zip');
    }
}
