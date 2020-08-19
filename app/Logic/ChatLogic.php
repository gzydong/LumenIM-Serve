<?php

namespace App\Logic;

use App\Facades\SocketResourceHandle;
use App\Models\{
    UsersChatList,
    UsersChatRecords,
    UsersChatRecordsForward,
    UsersFriends,
    UsersGroup
};

use Illuminate\Support\Facades\DB;
use App\Helpers\Cache\CacheHelper;

class ChatLogic extends Logic
{
    /**
     * 获取用户的聊天列表
     *
     * @param int $user_id 用户ID
     * @return mixed
     */
    public function getUserChatList(int $user_id)
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
     * 创建聊天列表记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID
     * @param int $type 创建类型 1:私聊  2:群聊
     * @return int
     */
    public function createChatList(int $user_id, int $receive_id, int $type)
    {
        $result = UsersChatList::where('uid', $user_id)->where('type', $type)->where($type == 1 ? 'friend_id' : 'group_id', $receive_id)->first();
        if ($result) {
            $result->status = 1;
            $result->updated_at = date('Y-m-d H:i:s');
            $result->save();
        } else {
            if (!$result = UsersChatList::create([
                'type' => $type,
                'uid' => $user_id,
                'status' => 1,
                'friend_id' => $type == 1 ? $receive_id : 0,
                'group_id' => $type == 2 ? $receive_id : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])) {
                return 0;
            }
        }
        return $result->id;
    }

    /**
     * 删除聊天消息
     *
     * @param int $user_id 用户ID
     * @param int $source 消息来源
     * @param int $receive_id 好友ID或者群聊ID
     * @param array $record_ids 聊天记录ID
     * @return bool
     */
    public function removeRecords(int $user_id, int $source, int $receive_id, array $record_ids)
    {
        if ($source == 1) {//私聊信息
            $ids = UsersChatRecords::whereIn('id', $record_ids)->where(function ($query) use ($user_id, $receive_id) {
                $query->where([['user_id', '=', $user_id], ['receive_id', '=', $receive_id]])->orWhere([['user_id', '=', $receive_id], ['receive_id', '=', $user_id]]);
            })->where('source', 1)->pluck('id');
        } else {//群聊信息
            $ids = UsersChatRecords::whereIn('id', $record_ids)->whereIn('id', $record_ids)->where('source', 2)->pluck('id');
        }

        if (count($ids) != count($record_ids)) {
            return false;
        }

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

        return DB::table('users_chat_records_del')->insert($data);
    }

    /**
     * 撤回聊天消息
     *
     * @param int $user_id 用户ID
     * @param int $record_id 聊天记录ID
     * @return array
     */
    public function revokeRecords(int $user_id, int $record_id)
    {
        $result = UsersChatRecords::where('id', $record_id)->first(['id', 'source', 'user_id', 'receive_id', 'send_time']);
        if (!$result) return [false, '消息记录不存在'];

        //判断是否在两分钟之内撤回消息，超过2分钟不能撤回消息
        if ((time() - strtotime($result->send_time) > 120)) {
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
     * 删除聊天列表
     *
     * @param int $user_id 用户ID
     * @param int $id 聊天列表ID、好友ID或群聊ID
     * @param int $type ID类型 （1：聊天列表ID  2:好友ID  3:群聊ID）
     * @return bool
     */
    public function delChatList(int $user_id, int $id, $type = 1)
    {
        if ($type == 1) {
            return (bool)UsersChatList::where('id', $id)->where('uid', $user_id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        if ($type == 2) {
            return (bool)UsersChatList::where('uid', $user_id)->where('friend_id', $id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        return (bool)UsersChatList::where('uid', $user_id)->where('group_id', $id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * 聊天对话列表置顶操作
     *
     * @param int $user_id 用户ID
     * @param int $list_id 对话列表ID
     * @param bool $is_top 是否置顶（true:是 false:否）
     * @return bool
     */
    public function chatListTop(int $user_id, int $list_id, $is_top = true)
    {
        return (bool)UsersChatList::where('id', $list_id)->where('uid', $user_id)->update(['is_top' => $is_top ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * 设置消息免打扰
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID
     * @param int $type 接收者类型（1:好友  2:群组）
     * @param int $not_disturb 是否免打扰
     * @return boolean
     */
    public function setNotDisturb(int $user_id, int $receive_id, int $type, int $not_disturb)
    {
        $result = UsersChatList::where('uid', $user_id)->where($type == 1 ? 'friend_id' : 'group_id', $receive_id)->where('status', 1)->first(['id', 'not_disturb']);
        if (!$result || $not_disturb == $result->not_disturb) {
            return false;
        }

        return (bool)UsersChatList::where('id', $result->id)->update(['not_disturb' => $not_disturb]);
    }

    /**
     * 消息逐条转发逻辑
     *
     * @param int $user_id 当前用户ID
     * @param int $source 消息来源
     * @param int $receive_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param array $records_ids 转发消息的记录ID
     * @param array $user_ids 转发消息的接收用户
     * @param array $group_ids 转发消息的群组
     * @return boolean
     */
    public function forwardRecords(int $user_id, int $source, int $receive_id, array $records_ids, $user_ids = [], $group_ids = [])
    {
        $num = count($records_ids);

        $sqlObj = null;

        //验证是否有权限转发
        if ($source == 2) {//群聊消息
            //判断是否是群聊成员
            if (!UsersGroup::isMember($receive_id, $user_id)) return false;

            $sqlObj = UsersChatRecords::whereIn('id', $records_ids)->where('receive_id', $receive_id)->whereIn('msg_type', [1, 2])->where('source', 2)->where('is_revoke', 0);
        } else {//私聊消息
            //判断是否存在好友关系
            if (!UsersFriends::isFriend($user_id, $receive_id)) return false;

            $sqlObj = UsersChatRecords::whereIn('id', $records_ids)
                ->where(function ($query) use ($user_id, $receive_id) {
                    $query->where([
                        ['user_id', '=', $user_id],
                        ['receive_id', '=', $receive_id]
                    ])->orWhere([
                        ['user_id', '=', $receive_id],
                        ['receive_id', '=', $user_id]
                    ]);
                })->whereIn('msg_type', [1, 2])->where('source', 1)->where('is_revoke', 0);
        }

        $result = $sqlObj->get();
        //判断消息记录是否存在
        if (count($result) != $num) return false;

        $receive_arr = [];
        if ($user_ids) {
            foreach ($user_ids as $friend_id) {
                $receive_arr[] = ['receive_id' => $friend_id, 'source' => 1];
            }
        }

        if ($group_ids) {
            foreach ($group_ids as $group_id) {
                $receive_arr[] = ['receive_id' => $group_id, 'source' => 2];
            }
        }

        $array = [];
        foreach ($result as $item) {
            foreach ($receive_arr as $receive) {
                $array[] = [
                    'source' => $receive['source'],
                    'msg_type' => $item['msg_type'],
                    'user_id' => $user_id,
                    'receive_id' => $receive['receive_id'],
                    'file_id' => $item['file_id'],
                    'forward_id' => 0,
                    'content' => $item['content'],
                    'is_code' => $item['is_code'],
                    'code_lang' => $item['code_lang'],
                    'send_time' => date('Y-m-d H:i:s')
                ];
            }
        }

        $ids = [];
        DB::beginTransaction();
        try {
            foreach ($array as $_data) {
                $record_id = DB::table('users_chat_records')->insertGetId($_data);
                if (!$record_id) {
                    throw new \Exception('插入记录失败');
                }

                $ids[] = $record_id;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        return $ids;
    }

    /**
     * 消息合并转发逻辑
     *
     * @param int $user_id 当前用户ID
     * @param int $source 消息来源
     * @param int $receive_id 当前转发消息的所属者(好友ID或者群聊ID)
     * @param array $records_ids 转发消息的记录ID
     * @param array $user_ids 转发消息的接收用户
     * @param array $group_ids 转发消息的群组
     * @return boolean
     */
    public function mergeForwardRecords(int $user_id, int $source, int $receive_id, array $records_ids, $user_ids = [], $group_ids = [])
    {
        $num = count($records_ids);
        if ($num <= 1) return false;

        //验证是否有权限转发
        if ($source == 2) {//群聊消息
            //判断是否是群聊成员
            if (!UsersGroup::isMember($receive_id, $user_id)) return false;

            $checkNum = UsersChatRecords::whereIn('id', $records_ids)->where('receive_id', $receive_id)->whereIn('msg_type', [1, 2])->where('source', 2)->where('is_revoke', 0)->count();
        } else {//私聊消息
            //判断是否存在好友关系
            if (!UsersFriends::isFriend($user_id, $receive_id)) return false;

            $checkNum = UsersChatRecords::whereIn('id', $records_ids)
                ->where(function ($query) use ($user_id, $receive_id) {
                    $query->where([
                        ['user_id', '=', $user_id],
                        ['receive_id', '=', $receive_id]
                    ])->orWhere([
                        ['user_id', '=', $receive_id],
                        ['receive_id', '=', $user_id]
                    ]);
                })->whereIn('msg_type', [1, 2])->where('source', 1)->where('is_revoke', 0)->count();
        }

        //判断消息记录是否存在
        if ($checkNum != $num) return false;

        $rows = UsersChatRecords::leftJoin('users', 'users.id', '=', 'users_chat_records.user_id')
            ->whereIn('users_chat_records.id', array_slice($records_ids, 0, 3))
            ->get(['users_chat_records.msg_type', 'users_chat_records.content', 'users_chat_records.is_code', 'users.nickname']);
        $arr = [];
        foreach ($rows as $row) {
            $text = substr(str_replace(PHP_EOL, "", $row->content), 0, 30);
            $arr[] = [
                'nickname' => $row->nickname,
                'text' => $row->msg_type == 1 ? ($row->is_code ? '代码消息' : $text) : '文件消息'
            ];
        }

        $ids = [];
        DB::beginTransaction();
        try {
            $result = UsersChatRecordsForward::create([
                'user_id' => $user_id,
                'records_id' => implode(',', $records_ids),
                'text' => json_encode($arr),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) throw new \Exception('插入转发记录数失败...');

            if ($user_ids) {
                foreach ($user_ids as $user_receive_id) {
                    $record_id = DB::table('users_chat_records')->insertGetId([
                        'source' => 1,
                        'msg_type' => 4,
                        'user_id' => $user_id,
                        'receive_id' => $user_receive_id,
                        'forward_id' => $result->id,
                        'send_time' => date('Y-m-d H:i:s')
                    ]);

                    if (!$record_id) throw new \Exception('新增聊天记录失败...');

                    $ids[] = $record_id;
                }
            }

            if ($group_ids) {
                foreach ($group_ids as $group_receive_id) {
                    $record_id = DB::table('users_chat_records')->insertGetId([
                        'source' => 2,
                        'msg_type' => 4,
                        'user_id' => $user_id,
                        'receive_id' => $group_receive_id,
                        'forward_id' => $result->id,
                        'send_time' => date('Y-m-d H:i:s')
                    ]);

                    if (!$record_id) throw new \Exception('新增聊天记录失败...');

                    $ids[] = $record_id;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        return $ids;
    }

    /**
     * 获取会话记录信息
     *
     * @param int $user_id 当前用户ID
     * @param int $records_id 聊天记录ID
     * @return array
     */
    public function getForwardRecords(int $user_id, int $records_id)
    {
        $recordsInfo = UsersChatRecords::join('users_chat_records_forward', 'users_chat_records_forward.id', '=', 'users_chat_records.forward_id')
            ->where('users_chat_records.id', $records_id)
            ->first([
                'users_chat_records.id',
                'users_chat_records.source',
                'users_chat_records.msg_type',
                'users_chat_records.user_id',
                'users_chat_records.receive_id',
                'users_chat_records.forward_id',
                'users_chat_records_forward.records_id'
            ]);

        if (!$recordsInfo) return [];

        //判断是否有权限查看
        if ($recordsInfo->source == 1 && ($recordsInfo->user_id != $user_id && $recordsInfo->receive_id != $user_id)) {
            return [];
        } else if ($recordsInfo->source == 2 && !UsersGroup::isMember($recordsInfo->receive_id, $user_id)) {
            return [];
        }

        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',
            'users_chat_records.is_code',
            'users_chat_records.code_lang',
            'users_chat_records.is_revoke',
            'users_chat_records.forward_id',

            'users_chat_records.file_id',
            'users_chat_files.file_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name as file_original_name',

            'users.nickname',
            'users.avatar as avatar',
        ]);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id');
        $rowsSqlObj->leftJoin('users_chat_files', 'users_chat_files.id', '=', 'users_chat_records.file_id');
        $rowsSqlObj->whereIn('users_chat_records.id', explode(',', $recordsInfo->records_id));

        return array_map(function ($item) {
            $item['file_url'] = '';
            $item['friend_remarks'] = '';

            //消息类型处理
            switch ($item['msg_type']) {
                case 1://文字消息
                    if ($item['is_code'] == 0) {
                        $item['content'] = replaceUrlToLink($item['content']);
                        $item['content'] = emojiReplace($item['content']);
                    } else {
                        $item['content'] = htmlspecialchars_decode($item['content']);
                    }
                    break;
                case 2://文字消息
                    $item['file_url'] = ($item['msg_type'] == 2) ? getFileUrl($item['save_dir']) : '';
                    break;
            }

            return $item;
        }, $rowsSqlObj->get()->toArray());
    }

    /**
     * 查找历史聊天记录信息
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID(用户ID或群聊接收ID)
     * @param int $source 聊天来源（1:私信 2:群聊）
     * @param int $find_type 搜索消息类型(0:全部信息 1:图片信息 2:文件信息)
     * @param int $find_mode 记录查找方式(0:获取最新的数据 1:向上查找 2:向下查找)
     * @param int $record_id 记录节点ID
     * @param int $limit 数据大小
     * @return mixed
     */
    public function findChatRecords(int $user_id, int $receive_id, int $source, int $find_type, int $find_mode, int $record_id, $limit = 30)
    {
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',
            'users_chat_records.is_code',
            'users_chat_records.code_lang',
            'users_chat_records.is_revoke',
            'users_chat_records.forward_id',

            'users_chat_records.file_id',
            'users_chat_files.file_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name as file_original_name',

            'users.nickname',
            'users.avatar as avatar',
        ]);


        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id');

        $joinType = $find_type == 0 ? 'leftJoin' : 'join';
        $rowsSqlObj->{$joinType}('users_chat_files', function ($join) use ($find_type) {
            $join->on('users_chat_files.id', '=', 'users_chat_records.file_id');
            if ($find_type == 1) {
                $join->where('users_chat_files.file_type', 1);
            } else if ($find_type == 2) {
                $join->where('users_chat_files.file_type', 3);
            }
        });

        if ($find_mode == 3) {
            $rowsSqlObj->where('users_chat_records.id', '>=', $record_id);
        } else if ($find_mode > 0) {
            $rowsSqlObj->where('users_chat_records.id', $find_mode == 1 ? '<' : '>', $record_id);
        }

        if ($source == 1) {
            $rowsSqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['users_chat_records.user_id', '=', $user_id],
                    ['users_chat_records.receive_id', '=', $receive_id]
                ])->orWhere([
                    ['users_chat_records.user_id', '=', $receive_id],
                    ['users_chat_records.receive_id', '=', $user_id]
                ]);
            });
        } else {
            $rowsSqlObj->where('users_chat_records.receive_id', $receive_id);
            $rowsSqlObj->where('users_chat_records.source', $source);
            $rowsSqlObj->whereIn('users_chat_records.msg_type', [1, 2, 5]);
        }

        $orderBy = 'asc';
        if ($find_mode == 0 || $find_mode == 1) {
            $orderBy = 'desc';
        }

        $rowsSqlObj->orderBy('users_chat_records.id', $orderBy);
        return $rowsSqlObj->limit($limit)->get()->toArray();
    }

    /**
     * 关键词搜索聊天记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID(用户ID或群聊接收ID)
     * @param int $source 聊天来源（1:私信 2:群聊）
     * @param int $page 当前查询分页
     * @param int $page_size 分页大小
     * @param array $params 查询参数
     * @return mixed
     */
    public function searchChatRecords(int $user_id, int $receive_id, int $source, int $page, int $page_size, array $params)
    {
        $countSqlObj = UsersChatRecords::select();
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',
            'users_chat_records.is_code',
            'users_chat_records.code_lang',
            'users_chat_records.is_revoke',
            'users_chat_records.forward_id',

            'users_chat_records.file_id',
            'users_chat_files.file_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name as file_original_name',

            'users.nickname',
            'users.avatar as avatar',
        ]);

        $countSqlObj->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id');
        $countSqlObj->leftJoin('users_chat_files', function ($join) {
            $join->on('users_chat_files.id', '=', 'users_chat_records.file_id');
            $join->where('users_chat_files.file_type', 3);
        });

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id');
        $rowsSqlObj->leftJoin('users_chat_files', function ($join) {
            $join->on('users_chat_files.id', '=', 'users_chat_records.file_id');
            $join->where('users_chat_files.file_type', 3);
        });

        if ($source == 1) {
            $countSqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['users_chat_records.user_id', '=', $user_id],
                    ['users_chat_records.receive_id', '=', $receive_id]
                ])->orWhere([
                    ['users_chat_records.user_id', '=', $receive_id],
                    ['users_chat_records.receive_id', '=', $user_id]
                ]);
            });

            $rowsSqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['users_chat_records.user_id', '=', $user_id],
                    ['users_chat_records.receive_id', '=', $receive_id]
                ])->orWhere([
                    ['users_chat_records.user_id', '=', $receive_id],
                    ['users_chat_records.receive_id', '=', $user_id]
                ]);
            });
        } else {
            $countSqlObj->where('users_chat_records.receive_id', $receive_id);
            $countSqlObj->where('users_chat_records.source', $source);
            $countSqlObj->whereIn('users_chat_records.msg_type', [1, 2]);

            $rowsSqlObj->where('users_chat_records.receive_id', $receive_id);
            $rowsSqlObj->where('users_chat_records.source', $source);
            $rowsSqlObj->whereIn('users_chat_records.msg_type', [1, 2]);
        }

        if (isset($params['keywords'])) {
            $countSqlObj->where('users_chat_records.content', 'like', "%{$params['keywords']}%");
            $rowsSqlObj->where('users_chat_records.content', 'like', "%{$params['keywords']}%");
        }

        if (isset($params['date'])) {
            $countSqlObj->whereDate('users_chat_records.send_time', $params['date']);
            $rowsSqlObj->whereDate('users_chat_records.send_time', $params['date']);
        }

        $count = $countSqlObj->count();
        $rows = [];
        if ($count > 0) {
            $rows = $rowsSqlObj->orderBy('users_chat_records.id', 'desc')->forPage($page, $page_size)->get()->toArray();
        }

        return $this->packData($rows, $count, $page, $page_size);
    }

    /**
     * 获取聊天记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID(用户ID或群聊接收ID)
     * @param int $source 聊天来源（1:私信 2:群聊）
     * @param int $record_id 上一次记录ID数
     * @param int $limit 分页大小
     * @param array $msg_type 消息类型
     * @return mixed
     */
    public function getChatsRecords(int $user_id, int $receive_id, int $source, int $record_id, int $limit, $msg_type = [])
    {
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',
            'users_chat_records.is_code',
            'users_chat_records.code_lang',
            'users_chat_records.is_revoke',
            'users_chat_records.forward_id',

            'users_chat_records.file_id',
            'users_chat_files.file_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name as file_original_name',

            'users.nickname',
            'users.avatar as avatar',
        ]);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id');
        $rowsSqlObj->leftJoin('users_chat_files', 'users_chat_files.id', '=', 'users_chat_records.file_id');

        if ($record_id) {
            $rowsSqlObj->where('users_chat_records.id', '<', $record_id);
        }

        if ($source == 1) {
            $rowsSqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['users_chat_records.user_id', '=', $user_id],
                    ['users_chat_records.receive_id', '=', $receive_id]
                ])->orWhere([
                    ['users_chat_records.user_id', '=', $receive_id],
                    ['users_chat_records.receive_id', '=', $user_id]
                ]);
            });
        } else {
            $rowsSqlObj->where('users_chat_records.receive_id', $receive_id);
            $rowsSqlObj->where('users_chat_records.source', $source);
        }

        if ($msg_type) {
            $rowsSqlObj->whereIn('users_chat_records.msg_type', $msg_type);
        }

        //过滤用户删除记录
        $rowsSqlObj->whereNotExists(function ($query) use ($user_id) {
            $query->select(DB::raw(1))->from('users_chat_records_del');

            $prefix = DB::getConfig('prefix');
            $query->whereRaw("{$prefix}users_chat_records_del.record_id = {$prefix}users_chat_records.id and {$prefix}users_chat_records_del.user_id = {$user_id}");
            $query->limit(1);
        });

        $rowsSqlObj->orderBy('users_chat_records.id', 'desc');
        return $rowsSqlObj->limit($limit)->get()->toArray();
    }

    /**
     * 获取消息记录上下文
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID(用户ID或群聊接收ID)
     * @param int $source 聊天来源（1:私信 2:群聊）
     * @param int $find_mode 查询方式(1:向上查询 2:向下查询)
     * @param int $record_id 上一次记录ID数
     * @param int $limit 分页大小
     * @param array $msg_type 消息类型
     * @return mixed
     */
    public function getRecordsContexts(int $user_id, int $receive_id, int $source, int $find_mode, int $record_id, int $limit, $msg_type = [], $isContainRecordId = false)
    {
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',
            'users_chat_records.is_code',
            'users_chat_records.code_lang',
            'users_chat_records.is_revoke',
            'users_chat_records.forward_id',

            'users_chat_records.file_id',
            'users_chat_files.file_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name as file_original_name',

            'users.nickname',
            'users.avatar as avatar',
        ]);

        $rowsSqlObj->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id');
        $rowsSqlObj->leftJoin('users_chat_files', 'users_chat_files.id', '=', 'users_chat_records.file_id');

        if ($record_id) {
            if ($isContainRecordId) {
                $rowsSqlObj->where('users_chat_records.id', $find_mode == 1 ? '<=' : '>=', $record_id);
            } else {
                $rowsSqlObj->where('users_chat_records.id', $find_mode == 1 ? '<' : '>', $record_id);
            }
        }

        if ($source == 1) {
            $rowsSqlObj->where(function ($query) use ($user_id, $receive_id) {
                $query->where([
                    ['users_chat_records.user_id', '=', $user_id],
                    ['users_chat_records.receive_id', '=', $receive_id]
                ])->orWhere([
                    ['users_chat_records.user_id', '=', $receive_id],
                    ['users_chat_records.receive_id', '=', $user_id]
                ]);
            });
        } else {
            $rowsSqlObj->where('users_chat_records.receive_id', $receive_id);
            $rowsSqlObj->where('users_chat_records.source', $source);
        }

        if ($msg_type) {
            $rowsSqlObj->whereIn('users_chat_records.msg_type', $msg_type);
        }

        //过滤用户删除记录
        $rowsSqlObj->whereNotExists(function ($query) use ($user_id) {
            $query->select(DB::raw(1))->from('users_chat_records_del');
            $prefix = DB::getConfig('prefix');
            $query->whereRaw("{$prefix}users_chat_records_del.record_id = {$prefix}users_chat_records.id and {$prefix}users_chat_records_del.user_id = {$user_id}");
            $query->limit(1);
        });

        if ($find_mode == 1) {
            $rowsSqlObj->orderBy('users_chat_records.id', 'desc');
        } else {
            $rowsSqlObj->orderBy('users_chat_records.id', 'asc');
        }

        return $rowsSqlObj->limit($limit)->get()->toArray();
    }
}
