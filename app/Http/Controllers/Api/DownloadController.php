<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends CController
{

    /**
     * 用户的聊天文件
     * @return mixed
     */
    public function userChatFile(Request $request){
        $fileId = $request->get('f_id',0);
        $crId = $request->get('cr_id',0);
//        if(!isInt($fileId) || !isInt($crId)){
//            return '';
//            return $this->ajaxError('文件下载失败...');
//        }


        return Storage::disk('uploads')->download('user-file/20191220/im-5dfc87baad1b0-5dfc87baad1b3.zip','wl_shop.zip');
    }
}
