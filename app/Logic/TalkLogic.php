<?php

namespace App\Logic;

use App\Models\ChatRecords;
use App\Models\ChatRecordsCode;
use App\Models\ChatRecordsFile;
use App\Models\ChatRecordsForward;
use App\Models\ChatRecordsInvite;
use App\Models\User;
use App\Models\UsersGroup;
use Illuminate\Support\Facades\DB;

class TalkLogic extends Logic
{
    /**
     *  用户对话列表
     */
    public function talkLists()
    {

    }

    /**
     * 处理聊天记录信息
     *
     * @param array $rows 聊天记录
     * @return array
     */
    public function handleChatRecords(array $rows)
    {
        if (empty($rows)) return [];

        $files = $codes = $forwards = $invites = [];
        foreach ($rows as $value) {
            switch ($value['msg_type']) {
                case 2:
                    $files[] = $value['id'];
                    break;
                case 3:
                    $invites[] = $value['id'];
                    break;
                case 4:
                    $forwards[] = $value['id'];
                    break;
                case 5:
                    $codes[] = $value['id'];
                    break;
            }
        }

        // 查询聊天文件信息
        if ($files) {
            $files = ChatRecordsFile::whereIn('record_id', $files)->get(['id', 'record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir'])->keyBy('record_id')->toArray();
        }

        // 查询群聊邀请信息
        if ($invites) {
            $invites = ChatRecordsInvite::whereIn('record_id', $invites)->get(['record_id', 'type', 'operate_user_id', 'user_ids'])->keyBy('record_id')->toArray();
        }

        // 查询代码块消息
        if ($codes) {
            $codes = ChatRecordsCode::whereIn('record_id', $codes)->get(['record_id', 'code_lang', 'code'])->keyBy('record_id')->toArray();
        }

        // 查询消息转发信息
        if ($forwards) {
            $forwards = ChatRecordsForward::whereIn('record_id', $forwards)->get(['record_id', 'records_id', 'text'])->keyBy('record_id')->toArray();
        }

        foreach ($rows as $k => $row) {
            $rows[$k]['file'] = [];
            $rows[$k]['code_block'] = [];
            $rows[$k]['forward'] = [];
            $rows[$k]['invite'] = [];

            switch ($row['msg_type']) {
                case 1://1:文本消息
                    if (!empty($rows[$k]['content'])) {
                        $rows[$k]['content'] = emojiReplace(replaceUrlToLink($row['content']));
                    }
                    break;
                case 2://2:文件消息
                    $rows[$k]['file'] = $files[$row['id']] ?? [];
                    break;
                case 3://3:入群消息/退群消息
                    if (isset($invites[$row['id']])) {
                        $rows[$k]['invite'] = [
                            'type' => $invites[$row['id']]['type'],
                            'operate_user' => [
                                'id' => $invites[$row['id']]['operate_user_id'],
                                'nickname' => User::where('id', $invites[$row['id']]['operate_user_id'])->value('nickname')
                            ],
                            'users' => []
                        ];

                        if ($rows[$k]['invite']['type'] == 1 || $rows[$k]['invite']['type'] == 3) {
                            $rows[$k]['invite']['users'] = User::select('id', 'nickname')->whereIn('id', explode(',', $invites[$row['id']]['user_ids']))->get()->toArray();
                        } else {
                            $rows[$k]['invite']['users'] = $rows[$k]['invite']['operate_user'];
                        }
                    }
                    break;
                case 4://4:会话记录消息
                    if (isset($forwards[$row['id']])) {
                        $rows[$k]['forward'] = [
                            'num' => substr_count($forwards[$row['id']]['records_id'], ',') + 1,
                            'list' => json_decode($forwards[$row['id']]['text'], true) ?? []
                        ];
                    }
                    break;
                case 5://5:代码块消息
                    $rows[$k]['code_block'] = $codes[$row['id']] ?? [];
                    if ($rows[$k]['code_block']) {
                        $rows[$k]['code_block']['code'] = htmlspecialchars_decode($rows[$k]['code_block']['code']);
                        unset($rows[$k]['code_block']['record_id']);
                    }
                    break;
            }
        }

        unset($files, $codes, $forwards, $invites);
        return $rows;
    }

    /**
     * 查询对话页面的历史聊天记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID（好友ID或群ID）
     * @param int $source 接收者类型（1：好友  2：群）
     * @param int $record_id 上一次查询的聊天记录ID
     * @param int $limit 查询数据长度
     * @return mixed
     */
    public function getChatRecords(int $user_id, int $receive_id, int $source, int $record_id, int $limit)
    {
        $fields = [
            'chat_records.id',
            'chat_records.source',
            'chat_records.msg_type',
            'chat_records.user_id',
            'chat_records.receive_id',
            'chat_records.content',
            'chat_records.is_revoke',
            'chat_records.created_at',

            'users.nickname',
            'users.avatar as avatar',
        ];

        $rowsSqlObj = ChatRecords::select($fields);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'chat_records.user_id');
        if ($record_id) {
            $rowsSqlObj->where('chat_records.id', '<', $record_id);
        }

        if ($source == 1) {
            $rowsSqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['chat_records.user_id', '=', $user_id],
                    ['chat_records.receive_id', '=', $receive_id]
                ])->orWhere([
                    ['chat_records.user_id', '=', $receive_id],
                    ['chat_records.receive_id', '=', $user_id]
                ]);
            });
        } else {
            $rowsSqlObj->where('chat_records.receive_id', $receive_id);
            $rowsSqlObj->where('chat_records.source', $source);
        }

        //过滤用户删除记录
        $rowsSqlObj->whereNotExists(function ($query) use ($user_id) {
            $prefix = DB::getConfig('prefix');
            $query->select(DB::raw(1))->from('chat_records_delete');
            $query->whereRaw("{$prefix}chat_records_delete.record_id = {$prefix}chat_records.id and {$prefix}chat_records_delete.user_id = {$user_id}");
            $query->limit(1);
        });

        $rowsSqlObj->orderBy('chat_records.id', 'desc');

        // 聊天列表
        $rows = $rowsSqlObj->limit($limit)->get()->toArray();
        return $this->handleChatRecords($rows);
    }

    /**
     * 获取转发会话记录信息
     *
     * @param int $user_id 用户ID
     * @param int $record_id
     * @return array
     */
    public function getForwardRecords(int $user_id, int $record_id)
    {
        $result = ChatRecords::where('id', $record_id)->first([
            'id', 'source', 'msg_type', 'user_id', 'receive_id', 'content', 'is_revoke', 'created_at'
        ]);

        //判断是否有权限查看
        if ($result->source == 1 && ($result->user_id != $user_id && $result->receive_id != $user_id)) {
            return [];
        } else if ($result->source == 2 && !UsersGroup::isMember($result->receive_id, $user_id)) {
            return [];
        }

        $forward = ChatRecordsForward::where('record_id', $record_id)->first();

        $fields = [
            'chat_records.id',
            'chat_records.source',
            'chat_records.msg_type',
            'chat_records.user_id',
            'chat_records.receive_id',
            'chat_records.content',
            'chat_records.is_revoke',
            'chat_records.created_at',

            'users.nickname',
            'users.avatar as avatar',
        ];

        $rowsSqlObj = ChatRecords::select($fields);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'chat_records.user_id');
        $rowsSqlObj->whereIn('chat_records.id', explode(',', $forward->records_id));

        $rows = $rowsSqlObj->get()->toArray();
        return $this->handleChatRecords($rows);
    }
}
