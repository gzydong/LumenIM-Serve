<?php
namespace App\Logic;

use App\Models\Emoticon;
use App\Models\EmoticonDetails;
use App\Models\UsersEmoticon;

/**
 * 表情包处理逻辑层
 *
 * @package App\Logic
 */
class EmoticonLogic extends Logic{

    /**
     * 添加表情包至用户收藏栏
     * @param int $user_id 用户ID
     * @param int $emoticon_id 表情包ID
     * @return mixed
     */
    public function addUserEmoticon(int $user_id, int $emoticon_id)
    {
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if ($info) {
            $emoticon_ids = $info->emoticon_ids;
            if (in_array($emoticon_id, $emoticon_ids)) {
                return true;
            }

            $emoticon_ids[] = $emoticon_id;
            return UsersEmoticon::where('user_id', $user_id)->update(['emoticon_ids' => implode(',', $emoticon_ids)]) ? true : false;
        }

        return UsersEmoticon::create(['user_id' => $user_id, 'emoticon_ids' => $emoticon_id]) ? true : false;
    }

    /**
     * 移除用户表情包
     *
     * @param int $user_id 用户ID
     * @param int $emoticon_id 表情包ID
     * @return bool
     */
    public function removeUserEmoticon(int $user_id, int $emoticon_id){
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if(!$info){
            return false;
        }

        if (!in_array($emoticon_id, $info->emoticon_ids)) {
            return false;
        }

        $emoticon_ids = $info->emoticon_ids;
        foreach ($emoticon_ids as $k=>$id){
            if($id == $emoticon_id){unset($emoticon_ids[$k]);}
        }

        if(count($info->emoticon_ids) == count($emoticon_ids)){
            return false;
        }

        return UsersEmoticon::where('user_id', $user_id)->update(['emoticon_ids' => implode(',', $emoticon_ids)]) ? true : false;
    }
}
