<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\{Emoticon, EmoticonDetails, UsersEmoticon};
use App\Logic\EmoticonLogic;
use Illuminate\Support\Facades\Storage;

/**
 * 表情包相关服务接口
 *
 * Class EmoticonController
 * @package App\Http\Controllers\Api
 */
class EmoticonController extends CController
{
    public $request;
    public $emoticonLogic;

    public function __construct(Request $request, EmoticonLogic $emoticonLogic)
    {
        $this->request = $request;
        $this->emoticonLogic = $emoticonLogic;
    }

    /**
     * 获取用户表情包列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserEmoticon()
    {
        $emoticonList = [];

        $ids = UsersEmoticon::where('user_id', $this->uid())->value('emoticon_ids');
        if ($ids) {
            $items = Emoticon::select('id', 'name', 'url')->whereIn('id', $ids)->get();
            foreach ($items as $item) {
                $list = EmoticonDetails::where('emoticon_id', $item->id)->where('user_id', 0)->get(['id as media_id', 'url as src'])->toArray();
                array_walk($list, function (&$val) {
                    $val['src'] = getFileUrl($val['src']);
                });

                $emoticonList[] = [
                    'emoticon_id' => $item->id,
                    'url' => getFileUrl($item->url),
                    'name' => $item->name,
                    'list' => $list
                ];

                unset($list);
            }
        }

        $collectEmoticon = EmoticonDetails::where('user_id', $this->uid())->where('emoticon_id', 0)->get(['id as media_id', 'url as src'])->toArray();
        array_walk($collectEmoticon, function (&$val) {
            $val['src'] = getFileUrl($val['src']);
        });

        return $this->ajaxSuccess('success', [
            'sys_emoticon' => $emoticonList,
            'collect_emoticon' => $collectEmoticon
        ]);
    }

    /**
     * 获取系统表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemEmoticon()
    {
        $items = Emoticon::select('id', 'name', 'url')->get()->toArray();
        if ($items) {
            $ids = UsersEmoticon::where('user_id', $this->uid())->value('emoticon_ids') ?? [];

            array_walk($items, function (&$item) use ($ids) {
                $item['status'] = in_array($item['id'], $ids) ? 1 : 0;
                $item['url'] = getFileUrl($item['url']);
            });
        }

        return $this->ajaxSuccess('success', $items);
    }

    /**
     * 操作用户表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setUserEmoticon()
    {
        $emoticon_id = $this->request->post('emoticon_id', 0);
        $type = $this->request->post('type', 0);
        if (!isInt($emoticon_id) || !in_array($type, [1, 2])) {
            return $this->ajaxParamError();
        }

        if ($type == 1) {
            $emoticonInfo = Emoticon::select('id', 'name', 'url')->where('id', $emoticon_id)->first();
            if (!$emoticonInfo) {
                return $this->ajaxError('添加表情包失败...');
            }

            $isTrue = $this->emoticonLogic->addUserEmoticon($this->uid(), $emoticon_id);
            if (!$isTrue) {
                return $this->ajaxError('添加表情包失败...');
            }

            $list = EmoticonDetails::where('emoticon_id', $emoticonInfo->id)->get(['id as media_id', 'url as src'])->toArray();
            array_walk($list, function (&$val) {
                $val['src'] = getFileUrl($val['src']);
            });

            $data = [
                'emoticon_id' => $emoticonInfo->id,
                'url' => getFileUrl($emoticonInfo->url),
                'name' => $emoticonInfo->name,
                'list' => $list
            ];

            return $this->ajaxSuccess('添加表情包成功', $data);
        }

        $isTrue = $this->emoticonLogic->removeUserEmoticon($this->uid(), $emoticon_id);
        return $isTrue ? $this->ajaxSuccess('移除表情包成功...') : $this->ajaxError('移除表情包失败...');
    }

    /**
     * 收藏聊天图片的我的表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function collectEmoticon()
    {
        $id = $this->request->post('record_id', 0);
        if (!isInt($id)) {
            return $this->ajaxParamError();
        }

        [$isTrue, $data] = $this->emoticonLogic->collectEmoticon($this->uid(), $id);

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
        $filename = getSaveImgName($ext, $imgInfo[0], $imgInfo[1]);

        if (!$save_path = Storage::disk('uploads')->putFileAs('images/emoticon/' . date('Ymd'), $file, $filename)) {
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
            'media_id' => $result->id, 'src' => getFileUrl($result->url)
        ]) : $this->ajaxError('表情包上传失败...');
    }

    /**
     * 移除收藏的表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delCollectEmoticon(){
        $ids = $this->request->post('ids', '');
        if(empty($ids)){
            return $this->ajaxParamError();
        }

        $ids = explode(',',trim($ids));
        if(!checkIds($ids)){
            return $this->ajaxParamError();
        }

        $isTrue = EmoticonDetails::whereIn('id',$ids)->where('user_id',$this->uid())->delete();

        return $isTrue ? $this->ajaxSuccess('success') : $this->ajaxError('fail');
    }
}
