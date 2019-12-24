<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\UsersChatFiles;
use App\Logic\FileSplitUploadLogic;
use Illuminate\Support\Str;
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

        return $this->ajaxSuccess('文件上传成功...',['avatar'=>getFileUrl($path)]);
    }

    /**
     * 获取拆分文件信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
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

        if(($info['split_index'] + 1) == $info['split_num']){
            $fileInfo = $logic->fileMerge($info['hash']);
            if(!$fileInfo){
                return $this->ajaxError('上传文件失败...');
            }

            $file_hahs_name = uniqid().Str::random().'.'.$info['ext'];
            $save_dir = "user-file/".date('Ymd').'/'.$file_hahs_name;
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

                return $this->ajaxSuccess('文件上传成功...',[
                    'is_file_merge'=>true,
                    'file_info'=> encrypt($res->id)
                ]);
            }
        }

        return $this->ajaxSuccess('文件上传成功...',['is_file_merge'=>false]);
    }

}
