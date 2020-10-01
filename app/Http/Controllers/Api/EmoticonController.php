<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{Emoticon, EmoticonDetails};
use App\Services\EmoticonService;

/**
 * 表情包相关服务接口
 *
 * Class EmoticonController
 * @package App\Http\Controllers\Api
 */
class EmoticonController extends CController
{
    /**
     * @var Request
     */
    public $request;

    /**
     * @var EmoticonService
     */
    public $emoticonService;

    public function __construct(Request $request, EmoticonService $emoticonService)
    {
        $this->request = $request;
        $this->emoticonService = $emoticonService;
    }

    /**
     * 获取用户表情包列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserEmoticon()
    {
        $emoticonList = [];
        $user_id = $this->uid();

        if ($ids = $this->emoticonService->getInstallIds($user_id)) {
            $items = Emoticon::whereIn('id', $ids)->get(['id', 'name', 'url']);
            foreach ($items as $item) {
                $emoticonList[] = [
                    'emoticon_id' => $item->id,
                    'url' => get_media_url($item->url),
                    'name' => $item->name,
                    'list' => $this->emoticonService->getDetailsAll([
                        ['emoticon_id', '=', $item->id],
                        ['user_id', '=', 0]
                    ])
                ];
            }
        }

        return $this->ajaxSuccess('success', [
            'sys_emoticon' => $emoticonList,
            'collect_emoticon' => $this->emoticonService->getDetailsAll([
                ['emoticon_id', '=', 0],
                ['user_id', '=', $user_id]
            ])
        ]);
    }

    /**
     * 获取系统表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemEmoticon()
    {
        $items = Emoticon::get(['id', 'name', 'url'])->toArray();
        if ($items) {
            $ids = $this->emoticonService->getInstallIds($this->uid());

            array_walk($items, function (&$item) use ($ids) {
                $item['status'] = in_array($item['id'], $ids) ? 1 : 0;
                $item['url'] = get_media_url($item['url']);
            });
        }

        return $this->ajaxSuccess('success', $items);
    }

    /**
     * 安装或移除系统表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setUserEmoticon()
    {
        $emoticon_id = $this->request->post('emoticon_id');
        $type = $this->request->post('type');
        if (!check_int($emoticon_id) || !in_array($type, [1, 2])) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();
        if ($type == 2) {//移除表情包
            $isTrue = $this->emoticonService->removeSysEmoticon($user_id, $emoticon_id);
            return $isTrue ? $this->ajaxSuccess('移除表情包成功...') : $this->ajaxError('移除表情包失败...');
        } else {//添加表情包
            $emoticonInfo = Emoticon::where('id', $emoticon_id)->first(['id', 'name', 'url']);
            if (!$emoticonInfo) {
                return $this->ajaxError('添加表情包失败...');
            }

            if (!$this->emoticonService->installSysEmoticon($user_id, $emoticon_id)) {
                return $this->ajaxError('添加表情包失败...');
            }

            $data = [
                'emoticon_id' => $emoticonInfo->id,
                'url' => get_media_url($emoticonInfo->url),
                'name' => $emoticonInfo->name,
                'list' => $this->emoticonService->getDetailsAll([
                    ['emoticon_id', '=', $emoticonInfo->id]
                ])
            ];

            return $this->ajaxSuccess('添加表情包成功', $data);
        }
    }

    /**
     * 收藏聊天图片的我的表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function collectEmoticon()
    {
        $id = $this->request->post('record_id');
        if (!check_int($id)) {
            return $this->ajaxParamError();
        }

        [$isTrue, $data] = $this->emoticonService->collect($this->uid(), $id);

        return $isTrue ? $this->ajaxSuccess('success', [
            'emoticon' => $data
        ]) : $this->ajaxError('添加表情失败');
    }

    /**
     * 自定义上传表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadEmoticon()
    {
        $file = $this->request->file('emoticon');
        if (!$file->isValid()) {
            return $this->ajaxParamError('图片上传失败，请稍后再试...');
        }

        $ext = $file->getClientOriginalExtension();
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->ajaxParamError('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        $imgInfo = getimagesize($file->getRealPath());
        $filename = create_image_name($ext, $imgInfo[0], $imgInfo[1]);

        if (!$save_path = Storage::disk('uploads')->putFileAs('media/images/emoticon/' . date('Ymd'), $file, $filename)) {
            return $this->ajaxError('图片上传失败');
        }

        $result = EmoticonDetails::create([
            'user_id' => $this->uid(),
            'url' => $save_path,
            'file_suffix' => $ext,
            'file_size' => $file->getSize(),
            'created_at' => time()
        ]);

        return $result ? $this->ajaxSuccess('success', [
            'media_id' => $result->id, 'src' => get_media_url($result->url)
        ]) : $this->ajaxError('表情包上传失败...');
    }

    /**
     * 移除收藏的表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delCollectEmoticon()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            return $this->ajaxParamError();
        }

        $ids = explode(',', trim($ids));
        if (!check_ids($ids)) {
            return $this->ajaxParamError();
        }

        return $this->emoticonService->deleteCollect($this->uid(), $ids) ?
            $this->ajaxSuccess('success') :
            $this->ajaxError('fail');
    }
}
