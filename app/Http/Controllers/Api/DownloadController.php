<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\{UsersChatFiles, UsersChatRecords, UsersGroup, ArticleAnnex};
use Illuminate\Support\Facades\Storage;

/**
 * 下载文件控制器模块
 *
 * Class DownloadController
 * @package App\Http\Controllers\Api
 */
class DownloadController extends CController
{

    /**
     * 下载用户聊天文件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userChatFile(Request $request)
    {
        $crId = $request->get('cr_id', 0);
        $uid = $this->uid();

        if (!isInt($crId)) {
            return $this->ajaxError('文件下载失败...');
        }

        $recordsInfo = UsersChatRecords::select(['msg_type', 'source', 'user_id', 'receive_id', 'file_id'])->where('id', $crId)->first();
        if (!$recordsInfo) {
            return $this->ajaxError('文件不存在...');
        }

        //判断消息是否是当前用户发送(如果是则跳过权限验证)
        if ($recordsInfo->user_id != $uid) {
            if ($recordsInfo->source == 1) {
                if ($recordsInfo->receive_id != $uid) {
                    return $this->ajaxError('非法请求...');
                }
            } else {
                if (!UsersGroup::checkGroupMember($recordsInfo->receive_id, $uid)) {
                    return $this->ajaxError('非法请求...');
                }
            }
        }

        $fileInfo = UsersChatFiles::select(['save_dir', 'original_name'])->where('id', $recordsInfo->file_id)->first();
        if (!$fileInfo) {
            return $this->ajaxError('文件不存在或没有下载权限...');
        }

        return $this->download($fileInfo->save_dir, $fileInfo->original_name);
    }

    /**
     * 下载笔记附件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function articleAnnex(Request $request)
    {
        $annex_id = $request->get('annex_id', 0);
        $uid = $this->uid();

        if (!isInt($annex_id)) {
            return $this->ajaxError('文件下载失败...');
        }

        $info = ArticleAnnex::select(['save_dir', 'original_name'])->where('id', $annex_id)->where('user_id', $uid)->first();
        if (!$info) {
            return $this->ajaxError('文件不存在或没有下载权限...');
        }

        return $this->download($info->save_dir, $info->original_name);
    }

    /**
     * 下载文件方法
     *
     * @param string $save_dir 文件相对地址
     * @param string $original_name 下载文件保存名称
     * @return mixed
     */
    private function download(string $save_dir, string $original_name)
    {
        return Storage::disk('uploads')->download($save_dir, null, [
            //解决中文下载问题
            'Content-Disposition' => "attachment; filename=\"{$original_name}\""
        ]);
    }
}
