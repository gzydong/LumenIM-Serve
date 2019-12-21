<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\FileSplitUpload;
use App\Models\UsersChatFiles;


use App\Logic\FileSplitUploadLogic;

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
     * 获取拆分文件信息
     */
    public function getFileSplitInfo(Request $request){
        if(!$request->filled(['file_name','file_size'])){
            return $this->ajaxParamError();
        }

        $logic = new FileSplitUploadLogic($this->uid());
        $data = $logic->createSplitInfo($request->post('file_name'),$request->post('file_size'));

        return $data?$this->ajaxSuccess('success',$data):$this->ajaxError('获取文件拆分信息失败...');
    }

    /**
     * 文件分区上传
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileSubareaUpload(Request $request){
        $file = $request->file('file');
        $params = ['name','hash','ext','size','split_index','split_num'];
        if(!$request->filled($params) || !$file){
            return $this->ajaxParamError();
        }

        $info = $request->only($params);
        $fileSize = $file->getClientSize();

        $logic = new FileSplitUploadLogic($this->uid());


        if(!$uploadRes = $logic->saveSplitFile($file,$info['hash'],$info['split_index'],$fileSize)){
            return $this->ajaxError('上传文件失败...');
        }


        $progress = ceil(($info['split_index'] + 1)/ $info['split_num'] * 100);
        if($progress == 100){
            $fileInfo = $logic->fileMerge($info['hash']);
            if(!$fileInfo){
                return $this->ajaxError('上传文件失败...');
            }

            $save_dir = "user-file/".date('Ymd').'/'.$fileInfo['tmp_file_name'];
            if(Storage::disk('uploads')->copy($fileInfo['path'],$save_dir)){
                $ext = pathinfo($fileInfo['original_name'], PATHINFO_EXTENSION);
                $res = UsersChatFiles::create([
                    'user_id'=>$this->uid(),
                    'file_type'=>UsersChatFiles::getFileType($ext),
                    'file_suffix'=>$ext,
                    'file_size'=>$fileInfo['file_size'],
                    'save_dir'=>$save_dir,
                    'original_name'=>$fileInfo['original_name'],
                    'created_at'=>date('Y-m-d H:i:s')
                ]);

                return $this->ajaxSuccess('文件上传成功...');
            }
        }

        return $this->ajaxSuccess('文件上传成功...');
    }

}
