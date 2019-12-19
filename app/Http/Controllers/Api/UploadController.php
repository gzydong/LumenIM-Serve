<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     */
    public function fileSubareaUpload(Request $request){
        $file = $request->file('file');
        if (!$file->isValid()) {
            return $this->ajaxParamError('请求参数错误');
        }


        //保存图片
        if(!$path = Storage::disk('uploads')->put('tmp/'.date('Ymd'), $file)){
            return $this->ajaxError('图片上传失败');
        }
    }

    /**
     * 分区文件请求合并操作
     * @param Request $request
     */
    public function fileMerge(Request $request){

    }
}
