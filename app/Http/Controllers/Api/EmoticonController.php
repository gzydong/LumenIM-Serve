<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Emoticon;
use App\Models\EmoticonDetails;
use App\Models\UsersEmoticon;
use App\Logic\EmoticonLogic;

class EmoticonController extends CController
{
    public $request;
    public $emoticonLogic;

    public function __construct(Request $request,EmoticonLogic $emoticonLogic)
    {
        $this->request = $request;
        $this->emoticonLogic = $emoticonLogic;
    }

    /**
     * 获取用户表情包列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserEmoticon(){
        $emoticon_list = [
            [
                'emoticon_id'=>0,
                'url'=>'https://g.alicdn.com/dingding/desktop-assets/1.1.1/img/face/icon_heart.png',
                'name'=>'我的收藏',
                'list'=>[]
            ]
        ];

        $ids = UsersEmoticon::where('user_id',$this->uid())->value('emoticon_ids');
        if($ids){
            $items = Emoticon::select('id','name','url')->whereIn('id',$ids)->get();
            foreach ($items as $item){
                $emoticon_list[] = [
                    'emoticon_id'=>$item->id,
                    'url'=>$item->url,
                    'name'=>$item->name,
                    'list'=>EmoticonDetails::where('emoticon_id',$item->id)->get(['id as media_id','url as src'])
                ];
            }
        }

        return $this->ajaxSuccess('success',$emoticon_list);
    }

    /**
     * 获取系统表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemEmoticon(){
        $items = Emoticon::select('id','name','url')->get()->toArray();
        if($items){
            $ids = UsersEmoticon::where('user_id',$this->uid())->value('emoticon_ids')??[];
            foreach ($items as $k => &$item){
                $item['status'] = in_array($item['id'],$ids) ? 1 : 0;
            }
        }

        return $this->ajaxSuccess('success',$items);
    }

    /**
     * 操作用户表情包
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setUserEmoticon(){
        $emoticon_id = $this->request->post('emoticon_id',0);
        $type = $this->request->post('type',0);
        if(!isInt($emoticon_id) || !in_array($type,[1,2])){
            return $this->ajaxParamError();
        }

        if($type == 1){
            $emoticonInfo = Emoticon::select('id','name','url')->where('id',$emoticon_id)->first();
            if(!$emoticonInfo){
                return $this->ajaxError('添加表情包失败...');
            }

            $isTrue = $this->emoticonLogic->addUserEmoticon($this->uid(),$emoticon_id);
            if(!$isTrue){
                return $this->ajaxError('添加表情包失败...');
            }

            $data = [
                'emoticon_id'=>$emoticonInfo->id,
                'url'=>$emoticonInfo->url,
                'name'=>$emoticonInfo->name,
                'list'=>EmoticonDetails::where('emoticon_id',$emoticonInfo->id)->get(['id as media_id','url as src'])
            ];

            return $this->ajaxSuccess('添加表情包成功',$data);
        }

        $isTrue = $this->emoticonLogic->removeUserEmoticon($this->uid(),$emoticon_id);
        return $isTrue ? $this->ajaxSuccess('移除表情包成功...') : $this->ajaxError('移除表情包失败...');
    }

    /**
     * 用户收集表情包
     */
    public function collectEmoticon(){

    }
}
