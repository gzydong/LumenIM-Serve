<?php

namespace App\Logic;

use App\Models\ChatRecords;
use App\Models\ChatRecordsCode;
use App\Models\ChatRecordsFile;
use App\Models\ChatRecordsForward;
use App\Models\ChatRecordsInvite;
use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersFriends;
use App\Models\UsersGroup;

use Illuminate\Support\Facades\DB;
use App\Facades\SocketResourceHandle;
use App\Helpers\Cache\CacheHelper;

class TalkLogic extends Logic
{
    /**
     * 获取用户的聊天列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function talkLists(int $user_id)
    {
        $filed = [
            'list.id', 'list.type', 'list.friend_id', 'list.group_id', 'list.updated_at', 'list.not_disturb', 'list.is_top',
            'users.avatar as user_avatar', 'users.nickname',
            'group.group_name', 'group.avatar as group_avatar'
        ];

        $rows = UsersChatList::from('users_chat_list as list')
            ->leftJoin('users', 'users.id', '=', 'list.friend_id')
            ->leftJoin('users_group as group', 'group.id', '=', 'list.group_id')
            ->where('list.uid', $user_id)
            ->where('list.status', 1)
            ->orderBy('updated_at', 'desc')
            ->get($filed)
            ->toArray();

        if (!$rows) return [];

        $rows = array_map(function ($item) use ($user_id) {
            $data['id'] = $item['id'];
            $data['type'] = $item['type'];
            $data['friend_id'] = $item['friend_id'];
            $data['group_id'] = $item['group_id'];
            $data['name'] = '';//对方昵称/群名称
            $data['unread_num'] = 0;//未读消息数量
            $data['avatar'] = '';//默认头像
            $data['remark_name'] = '';//好友备注
            $data['msg_text'] = '......';
            $data['updated_at'] = $item['updated_at'];
            $data['online'] = 0;
            $data['not_disturb'] = $item['not_disturb'];
            $data['is_top'] = $item['is_top'];

            if ($item['type'] == 1) {
                $data['name'] = $item['nickname'];
                $data['avatar'] = $item['user_avatar'];
                $data['unread_num'] = intval(CacheHelper::getChatUnreadNum($user_id, $item['friend_id']));
                $data['online'] = SocketResourceHandle::getUserFds($item['friend_id']) ? 1 : 0;

                $remark = CacheHelper::getFriendRemarkCache($user_id, $item['friend_id']);
                if (!is_null($remark)) {
                    $data['remark_name'] = $remark;
                } else {
                    $info = UsersFriends::select('user1', 'user2', 'user1_remark', 'user2_remark')
                        ->where('user1', ($user_id < $item['friend_id']) ? $user_id : $item['friend_id'])
                        ->where('user2', ($user_id < $item['friend_id']) ? $item['friend_id'] : $user_id)->first();
                    if ($info) {//这个环节待优化
                        $data['remark_name'] = ($info->user1 == $item['friend_id']) ? $info->user2_remark : $info->user1_remark;
                        CacheHelper::setFriendRemarkCache($user_id, $item['friend_id'], $data['remark_name']);
                    }
                }
            } else {
                $data['name'] = $item['group_name'];
                $data['avatar'] = $item['group_avatar'];
            }

            $records = CacheHelper::getLastChatCache($item['type'] == 1 ? $item['friend_id'] : $item['group_id'], $item['type'] == 1 ? $user_id : 0);

            if ($records) {
                $data['msg_text'] = $records['text'];
                $data['updated_at'] = $records['send_time'];
            }

            return $data;
        }, $rows);

        return $rows;
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
     * @param int $source 消息来源  1:好友消息 2:群聊消息
     * @param int $record_id 上一次查询的聊天记录ID
     * @param int $limit 查询数据长度
     * @return mixed
     */
    public function getChatRecords(int $user_id, int $receive_id, int $source, int $record_id, $limit = 30)
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
     * @param int $record_id 聊天记录ID
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

    /**
     * 批量删除聊天消息
     *
     * @param int $user_id 用户ID
     * @param int $source 消息来源  1:好友消息 2:群聊消息
     * @param int $receive_id 好友ID或者群聊ID
     * @param array $record_ids 聊天记录ID
     * @return bool
     */
    public function removeRecords(int $user_id, int $source, int $receive_id, array $record_ids)
    {
        if ($source == 1) {//私聊信息
            $ids = ChatRecords::whereIn('id', $record_ids)->where(function ($query) use ($user_id, $receive_id) {
                $query->where([['user_id', '=', $user_id], ['receive_id', '=', $receive_id]])->orWhere([['user_id', '=', $receive_id], ['receive_id', '=', $user_id]]);
            })->where('source', 1)->pluck('id');
        } else {//群聊信息
            $ids = ChatRecords::whereIn('id', $record_ids)->where('source', 2)->pluck('id');
        }

        // 判断要删除的消息在数据库中是否存在
        if (count($ids) != count($record_ids)) {
            return false;
        }

        // 判读是否属于群消息并且判断是否是群成员
        if ($source == 2 && !UsersGroup::isMember($receive_id, $user_id)) {
            return false;
        }

        $data = array_map(function ($record_id) use ($user_id) {
            return [
                'record_id' => $record_id,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }, $ids->toArray());

        return DB::table('chat_records_delete')->insert($data);
    }

    /**
     * 撤回单条聊天消息
     *
     * @param int $user_id 用户ID
     * @param int $record_id 聊天记录ID
     * @return array
     */
    public function revokeRecord(int $user_id, int $record_id)
    {
        $result = ChatRecords::where('id', $record_id)->first(['id', 'source', 'user_id', 'receive_id', 'created_at']);
        if (!$result) return [false, '消息记录不存在'];

        //判断是否在两分钟之内撤回消息，超过2分钟不能撤回消息
        if ((time() - strtotime($result->created_at) > 120)) {
            return [false, '已超过有效的撤回时间', []];
        }

        if ($result->source == 1) {
            if ($result->user_id != $user_id && $result->receive_id != $user_id) {
                return [false, '非法操作', []];
            }
        } else if ($result->source == 2) {
            if (!UsersGroup::isMember($result->receive_id, $user_id)) {
                return [false, '非法操作', []];
            }
        }

        $result->is_revoke = 1;
        $result->save();

        return [true, '消息已撤回', $result->toArray()];
    }

    /**
     * 转发消息（单条转发）
     *
     * @param int $user_id 转发的用户ID
     * @param int $record_id 转发消息的记录ID
     * @param array $receive_ids 接受者数组  例如:[['source' => 1,'id' => 3045],['source' => 1,'id' => 3046],['source' => 1,'id' => 1658]] 二维数组
     * @return array
     * @throws \Exception
     */
    public function forwardRecords(int $user_id, int $record_id, array $receive_ids)
    {
        $result = ChatRecords::where('id', $record_id)->whereIn('msg_type', [1, 2, 5])->first();
        if (!$result) {
            return [];
        }

        // 根据消息类型判断用户是否有转发权限
        if ($result->source == 1) {
            if ($result->user_id != $user_id && $result->receive_id != $user_id) {
                return [];
            }
        } else if ($result->source == 2) {
            if (!UsersGroup::isMember($result->receive_id, $user_id)) {
                return [];
            }
        }

        $fileInfo = null;
        $codeBlock = null;
        if ($result->msg_type == 2) {
            $fileInfo = ChatRecordsFile::where('record_id', $record_id)->first();
        } else if ($result->msg_type == 5) {
            $codeBlock = ChatRecordsCode::where('record_id', $record_id)->first();
        }

        $insRecordIds = [];
        DB::beginTransaction();
        try {
            foreach ($receive_ids as $item) {
                $res = ChatRecords::create([
                    'source' => $item['source'],
                    'msg_type' => $result->msg_type,
                    'user_id' => $user_id,
                    'receive_id' => $item['id'],
                    'content' => $result->content,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$res) {
                    throw new \Exception('插入消息记录失败');
                }

                $insRecordIds[] = $res->id;

                if ($result->msg_type == 2) {
                    if (!ChatRecordsFile::create([
                        'record_id' => $res->id,
                        'user_id' => $fileInfo->user_id,
                        'file_source' => $fileInfo->file_source,
                        'file_type' => $fileInfo->file_type,
                        'save_type' => $fileInfo->save_type,
                        'original_name' => $fileInfo->original_name,
                        'file_suffix' => $fileInfo->file_suffix,
                        'file_size' => $fileInfo->file_size,
                        'save_dir' => $fileInfo->save_dir,
                        'created_at' => date('Y-m-d H:i:s')
                    ])) {
                        throw new \Exception('插入文件消息记录失败');
                    }
                } else if ($result->msg_type == 5) {
                    if (!ChatRecordsCode::create([
                        'record_id' => $res->id,
                        'user_id' => $user_id,
                        'code_lang' => $codeBlock->code_lang,
                        'code' => $codeBlock->code,
                        'created_at' => date('Y-m-d H:i:s')
                    ])) {
                        throw new \Exception('插入代码消息记录失败');
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return [];
        }

        return $insRecordIds;
    }

    /**
     * 转发消息（多条合并转发）
     *
     * @param int $user_id 转发的用户ID
     * @param int $receive_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param int $source 消息来源  1:好友消息 2:群聊消息
     * @param array $records_ids 转发消息的记录ID
     * @param array $receive_ids 接受者数组  例如:[['source' => 1,'id' => 3045],['source' => 1,'id' => 3046],['source' => 1,'id' => 1658]] 二维数组
     * @return boolean
     */
    public function mergeForwardRecords(int $user_id, int $receive_id, int $source, $records_ids, array $receive_ids)
    {
        // 支持转发的消息类型
        $msg_type = [1, 2, 5];

        $sqlObj = ChatRecords::whereIn('id', $records_ids);

        //验证是否有权限转发
        if ($source == 2) {//群聊消息
            //判断是否是群聊成员
            if (!UsersGroup::isMember($receive_id, $user_id)) {
                return false;
            }

            $sqlObj = $sqlObj->where('receive_id', $receive_id)->whereIn('msg_type', $msg_type)->where('source', 2)->where('is_revoke', 0);
        } else {//私聊消息
            //判断是否存在好友关系
            if (!UsersFriends::isFriend($user_id, $receive_id)) {
                return false;
            }

            $sqlObj = $sqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['user_id', '=', $user_id],
                    ['receive_id', '=', $receive_id]
                ])->orWhere([
                    ['user_id', '=', $receive_id],
                    ['receive_id', '=', $user_id]
                ]);
            })->whereIn('msg_type', $msg_type)->where('source', 1)->where('is_revoke', 0);
        }

        $result = $sqlObj->get();

        //判断消息记录是否存在
        if (count($result) != count($records_ids)) {
            return false;
        }

        $rows = ChatRecords::leftJoin('users', 'users.id', '=', 'chat_records.user_id')
            ->whereIn('chat_records.id', array_slice($records_ids, 0, 3))
            ->get(['chat_records.msg_type', 'chat_records.content', 'users.nickname']);

        $jsonText = [];
        foreach ($rows as $row) {
            if ($row->msg_type == 1) {
                $jsonText[] = [
                    'nickname' => $row->nickname,
                    'text' => mb_substr(str_replace(PHP_EOL, "", $row->content), 0, 30)
                ];
            } else if ($row->msg_type == 2) {
                $jsonText[] = [
                    'nickname' => $row->nickname,
                    'text' => '【文件消息】'
                ];
            } else if ($row->msg_type == 5) {
                $jsonText[] = [
                    'nickname' => $row->nickname,
                    'text' => '【代码消息】'
                ];
            }
        }

        $jsonText = json_encode($jsonText);

        $insRecordIds = [];
        DB::beginTransaction();
        try {
            foreach ($receive_ids as $item) {
                $res = ChatRecords::create([
                    'source' => $item['source'],
                    'msg_type' => 4,
                    'user_id' => $user_id,
                    'receive_id' => $item['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$res) {
                    throw new \Exception('插入消息失败');
                }

                $insRecordIds[] = $res->id;

                if (!ChatRecordsForward::create([
                    'record_id' => $res->id,
                    'user_id' => $user_id,
                    'records_id' => implode(',', $records_ids),
                    'text' => $jsonText,
                    'created_at' => date('Y-m-d H:i:s'),
                ])) {
                    throw new \Exception('插入转发消息失败');
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [];
        }

        return $insRecordIds;
    }
}
