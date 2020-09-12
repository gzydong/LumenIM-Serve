<?php

namespace App\Logic;

use App\Models\Chat\{ChatRecords, ChatRecordsFile};
use App\Models\EmoticonDetails;
use App\Models\UsersEmoticon;
use App\Models\Group\UsersGroup;

/**
 * 表情包处理逻辑层
 *
 * @package App\Logic
 */
class EmoticonLogic extends BaseLogic
{

    /**
     * 添加表情包至用户收藏栏
     *
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
     * 移除收藏的系统表情包
     *
     * @param int $user_id 用户ID
     * @param int $emoticon_id 表情包ID
     * @return bool
     */
    public function removeUserEmoticon(int $user_id, int $emoticon_id)
    {
        $info = UsersEmoticon::select(['id', 'user_id', 'emoticon_ids'])->where('user_id', $user_id)->first();
        if (!$info) {
            return false;
        }

        if (!in_array($emoticon_id, $info->emoticon_ids)) {
            return false;
        }

        $emoticon_ids = $info->emoticon_ids;
        foreach ($emoticon_ids as $k => $id) {
            if ($id == $emoticon_id) {
                unset($emoticon_ids[$k]);
            }
        }

        if (count($info->emoticon_ids) == count($emoticon_ids)) {
            return false;
        }

        return UsersEmoticon::where('user_id', $user_id)->update(['emoticon_ids' => implode(',', $emoticon_ids)]) ? true : false;
    }

    /**
     * 聊天图片收藏至表情包
     *
     * @param int $user_id 用户ID
     * @param int $cid 聊天消息ID
     * @return array
     */
    public function collectEmoticon(int $user_id, int $cid)
    {
        $result = ChatRecords::where([
            ['id', '=', $cid],
            ['msg_type', '=', 2],
            ['is_revoke', '=', 0],
        ])->first(['id', 'source', 'msg_type', 'user_id', 'receive_id', 'is_revoke']);

        if (!$result) {
            return [false, []];
        }

        if ($result->source == 1) {
            if ($result->user_id != $user_id && $result->receive_id != $user_id) {
                return [false, []];
            }
        } else {
            if (!UsersGroup::isMember($result->receive_id, $user_id)) {
                return [false, []];
            }
        }

        $fileInfo = ChatRecordsFile::where('record_id', $result->id)->where('file_type', 1)->first([
            'file_suffix',
            'file_size',
            'save_dir'
        ]);

        if (!$fileInfo) {
            return [false, []];
        }

        $result = EmoticonDetails::where('user_id', $user_id)->where('url', $fileInfo->save_dir)->first();
        if ($result) {
            return [false, []];
        }

        $res = EmoticonDetails::create([
            'user_id' => $user_id,
            'url' => $fileInfo->save_dir,
            'file_suffix' => $fileInfo->file_suffix,
            'file_size' => $fileInfo->file_size,
            'created_at' => time()
        ]);

        return $res ? [true, ['media_id' => $res->id, 'src' => getFileUrl($res->url)]] : [false, []];
    }
}
