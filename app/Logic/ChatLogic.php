<?php

namespace App\Logic;

use App\Models\User;
use App\Models\UsersChatList;
use App\Models\UsersChatRecords;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Models\UsersGroupMember;
use Illuminate\Support\Facades\DB;
use App\Helpers\ImageCompose;

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
        $rows = UsersChatList::select(['users_chat_list.id', 'users_chat_list.type', 'users_chat_list.friend_id', 'users_chat_list.group_id', 'users_chat_list.updated_at'])
            ->where('users_chat_list.uid', $user_id)
            ->where('users_chat_list.status', 1)
            ->orderBy('updated_at', 'desc')->get()->toArray();

        if (empty($rows)) return [];

        $friend_ids = $group_ids = [];
        $rows = array_map(function ($item) use ($user_id, &$friend_ids, &$group_ids) {
            $item['name'] = '';//对方昵称/群名称
            $item['unread_num'] = 0;//未读消息数量
            $item['not_disturb'] = 0;//是否消息免打扰
            $item['group_members_num'] = 0;//群聊人数
            $item['avatar'] = '';//默认头像
            $item['remark_name'] = '';//好友备注
            $item['msg_text'] = '......';
            $item['created_at'] = $item['updated_at'];
            $item['online'] = 0;


            if ($item['type'] == 1) {
                $friend_ids[] = $item['friend_id'];
                $item['unread_num'] = intval(CacheHelper::getChatUnreadNum($user_id, $item['friend_id']));

                $item['online'] = app('SocketFdUtil')->getUserFds($item['friend_id']) ? 1 : 0;
            } else {
                $group_ids[] = $item['group_id'];
            }

            $records = CacheHelper::getLastChatCache($item['type'] == 1 ? $item['friend_id'] : $item['group_id'], $item['type'] == 1 ? $user_id : 0);
            if ($records) {
                $item['msg_text'] = $records['text'];
                $item['updated_at'] = $records['send_time'];
            }

            return $item;
        }, $rows);

        $friendInfos = $groupInfos = [];
        if ($group_ids) {
            $groupInfos = UsersGroupMember::select(['users_group.id', 'users_group.group_name', 'users_group.people_num', 'users_group.avatarurl', 'users_group_member.not_disturb'])
                ->join('users_group', 'users_group.id', '=', 'users_group_member.group_id')
                ->where('users_group_member.user_id', $user_id)
                ->whereIn('users_group_member.group_id', $group_ids)
                ->get()->toArray();
            $groupInfos = replaceArrayKey('id', $groupInfos);
        }

        if ($friend_ids) {
            $friendInfos = User::whereIn('id', $friend_ids)->get(['id', 'nickname', 'avatarurl'])->toArray();
            $friendInfos = replaceArrayKey('id', $friendInfos);
        }

        foreach ($rows as $key2 => $v2) {
            if ($v2['type'] == 1) {
                $rows[$key2]['avatar'] = $friendInfos[$v2['friend_id']]['avatarurl'] ?? '';
                $rows[$key2]['name'] = $friendInfos[$v2['friend_id']]['nickname'] ?? '';

                $remark = CacheHelper::getFriendRemarkCache($user_id, $v2['friend_id']);
                if (!is_null($remark)) {
                    $rows[$key2]['remark_name'] = $remark;
                    continue;
                }

                $info = UsersFriends::select('user1', 'user2', 'user1_remark', 'user2_remark')
                    ->where('user1', ($user_id < $v2['friend_id']) ? $user_id : $v2['friend_id'])
                    ->where('user2', ($user_id < $v2['friend_id']) ? $v2['friend_id'] : $user_id)->first();
                if ($info) {//这个环节待优化
                    $rows[$key2]['remark_name'] = ($info->user1 == $v2['friend_id']) ? $info->user2_remark : $info->user1_remark;
                    CacheHelper::setFriendRemarkCache($user_id, $v2['friend_id'], $rows[$key2]['remark_name']);
                }
            } else {
                $rows[$key2]['avatar'] = $groupInfos[$v2['group_id']]['avatarurl'] ?? '';
                $rows[$key2]['name'] = $groupInfos[$v2['group_id']]['group_name'] ?? '';
                $rows[$key2]['not_disturb'] = $groupInfos[$v2['group_id']]['not_disturb'] ?? 0;
            }
        }

        return $rows;
    }

    /**
     * 创建群聊
     * @param int $user_id 用户ID
     * @param string $group_name 群聊名称
     * @param string $group_avatar 群聊头像
     * @param string $group_profile 群聊用户ID(不包括群成员)
     * @param array $uids
     * @return array
     */
    public function launchGroupChat(int $user_id, string $group_name, string $group_avatar, string $group_profile, $uids = [])
    {
        array_unshift($uids, $user_id);
        $groupMember = [];
        $chatList = [];

        DB::beginTransaction();
        try {
            $insRes = UsersGroup::create(['user_id' => $user_id, 'group_name' => $group_name, 'avatarurl' => $group_avatar, 'group_profile' => $group_profile, 'people_num' => count($uids), 'status' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            if (!$insRes) {
                throw new \Exception('创建群失败');
            }

            foreach ($uids as $k => $uid) {
                $groupMember[] = [
                    'group_id' => $insRes->id,
                    'user_id' => $uid,
                    'group_owner' => ($k == 0) ? 1 : 0,
                    'status' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $chatList[] = [
                    'type' => 2,
                    'uid' => $uid,
                    'friend_id' => 0,
                    'group_id' => $insRes->id,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if (!DB::table('users_group_member')->insert($groupMember)) {
                throw new \Exception('创建群成员信息失败');
            }

            if (!DB::table('users_chat_list')->insert($chatList)) {
                throw new \Exception('创建群成员的聊天列表失败');
            }

            UsersChatRecords::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $insRes->id,
                'content' => implode(',', $uids),
                'send_time' => date('Y-m-d H:i;s')
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, []];
        }

        return [true, ['group_info' => $insRes->toArray(), 'uids' => $uids]];
    }

    /**
     * 邀请好友加入群聊
     *
     * @param int $user_id 用户ID
     * @param int $group_id 聊天群ID
     * @param array $uids 被邀请的用户ID
     * @return bool
     */
    public function inviteFriendsGroupChat(int $user_id, int $group_id, $uids = [])
    {
        $info = UsersGroupMember::select(['id', 'status'])->where('group_id', $group_id)->where('user_id', $user_id)->first();

        //判断主动邀请方是否属于聊天群成员
        if (!$info && $info->status == 1) return false;

        if (empty($uids)) return false;

        $updateArr = $insertArr = $updateArr1 = $insertArr1 = [];

        $members = UsersGroupMember::where('group_id', $group_id)->whereIn('user_id', $uids)->get(['id', 'user_id', 'status'])->toArray();
        $members = replaceArrayKey('user_id', $members);

        $cahtArr = UsersChatList::where('group_id', $group_id)->whereIn('uid', $uids)->get(['id', 'uid', 'status'])->toArray();
        $cahtArr = $cahtArr ? replaceArrayKey('uid', $cahtArr) : [];

        foreach ($uids as $uid) {
            if (!isset($members[$uid])) {//存在聊天群成员记录
                $insertArr[] = ['group_id' => $group_id, 'user_id' => $uid, 'group_owner' => 0, 'status' => 0, 'created_at' => date('Y-m-d H:i:s')];
            } else if ($members[$uid]['status'] == 1) {
                $updateArr[] = $members[$uid]['id'];
            }

            if (!isset($cahtArr[$uid])) {
                $insertArr1[] = ['type' => 2, 'uid' => $uid, 'friend_id' => 0, 'group_id' => $group_id, 'status' => 1, 'created_at' => date('Y-m-d H:i:s')];
            } else if ($cahtArr[$uid]['status'] == 0) {
                $updateArr1[] = $cahtArr[$uid]['id'];
            }
        }

        try {
            if ($updateArr) {
                UsersGroupMember::whereIn('id', $updateArr)->update(['status' => 0]);
            }

            if ($insertArr) {
                DB::table('users_group_member')->insert($insertArr);
            }

            if ($updateArr1) {
                UsersChatList::whereIn('id', $updateArr1)->update(['status' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }

            if ($insertArr1) {
                DB::table('users_chat_list')->insert($insertArr1);
            }

            $uidsStr = implode(',', $uids);
            UsersChatRecords::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $group_id,
                'content' => "{$user_id},{$uidsStr}",
                'send_time' => date('Y-m-d H:i;s')
            ]);

            UsersGroup::where('id', $group_id)->increment('people_num', count($uids));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 将指定的用户踢出群聊
     *
     * @param int $group_id 群ID
     * @param int $group_owner_id 操作用户ID
     * @param int $group_member_id 群成员ID
     * @return bool
     */
    public function removeGroupChat(int $group_id, int $group_owner_id, int $group_member_id)
    {
        if (!UsersGroup::where('id', $group_id)->where('user_id', $group_owner_id)->exists()) {
            return false;
        }

        DB::beginTransaction();
        try {
            if (!UsersGroupMember::where('group_id', $group_id)->where('user_id', $group_member_id)->where('group_owner', 0)->update(['status' => 0])) {
                throw new \Exception('修改群成员状态失败');
            }

            UsersGroup::where('group_id', $group_id)->decrement('people_num');

            UsersChatRecords::create([
                'msg_type' => 6,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $group_id,
                'content' => "{$group_owner_id},{$group_member_id}",
                'send_time' => date('Y-m-d H:i;s')
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 解散指定的群聊
     *
     * @param int $group_id 群ID
     * @param int $user_id 用户ID
     * @return bool
     */
    public function dismissGroupChat(int $group_id, int $user_id)
    {
        if (!UsersGroup::where('id', $group_id)->where('status', 0)->exists()) {
            return false;
        }

        //判断执行者是否属于群主
        if (!UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('group_owner', 1)->exists()) {
            return false;
        }

        DB::beginTransaction();
        try {
            UsersGroup::where('id', $group_id)->update(['status' => 1]);
            UsersGroupMember::where('group_id', $group_id)->update(['status' => 1]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 用户主动退出群聊
     *
     * @param int $group_id 群聊ID
     * @param int $user_id 用户ID
     * @return bool
     */
    public function quitGroupChat(int $group_id, int $user_id)
    {
        DB::beginTransaction();
        try {
            $res = UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('group_owner', 0)->update(['status' => 1]);
            if ($res) {
                UsersChatList::where('uid', $user_id)->where('type', 2)->where('group_id', $group_id)->update(['status' => 0]);

                UsersChatRecords::create([
                    'msg_type' => 6,
                    'source' => 2,
                    'user_id' => 0,
                    'receive_id' => $group_id,
                    'content' => $user_id,
                    'send_time' => date('Y-m-d H:i;s')
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            $res = false;
            DB::rollBack();
        }

        return $res ? true : false;
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
     * 获取聊天群
     *
     * @param int $user_id 用户ID
     * @param int $group_id 聊天群ID
     * @return array
     */
    public function getGroupDetail(int $user_id, int $group_id)
    {
        $groupInfo = UsersGroup::select(['id', 'user_id', 'group_name', 'people_num', 'group_profile', 'avatarurl', 'created_at'])->where('id', $group_id)->where('status', 0)->first();
        if (!$groupInfo) {
            return [];
        }

        //判断用户是否是群成员
        if (!UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('status', 0)->exists()) {
            return [];
        }

        $members = UsersGroupMember::select([
            'users_group_member.id', 'users_group_member.group_owner', 'users_group_member.visit_card',
            'users_group_member.user_id', 'users.avatarurl', 'users.nickname', 'users.mobile', 'users.gender',
            'users_group_member.not_disturb'
        ])
            ->leftJoin('users', 'users.id', '=', 'users_group_member.user_id')
            ->where([
                ['users_group_member.group_id', '=', $group_id],
                ['users_group_member.status', '=', 0],
            ])->get()->toArray();
        $disturb = 0;

        foreach ($members as $member) {
            if ($member['user_id'] == $user_id) {
                $disturb = $member['not_disturb'];
                break;
            }
        }


        return [
            'group_id' => $group_id,
            'user_id' => $groupInfo->user_id,
            'group_owner' => User::where('id', $groupInfo->user_id)->value('nickname'),
            'group_name' => $groupInfo->group_name,
            'group_profile' => $groupInfo->group_profile,
            'people_num' => $groupInfo->people_num,
            'group_avatar' => $groupInfo->avatarurl,
            'not_disturb' => $disturb,
            'created_at' => $groupInfo->created_at,
            'members' => $members
        ];
    }

    /**
     * 设置群聊免打扰
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群聊ID
     * @param int $status 免打扰状态 0:正常 1:接收但不提示
     * @return bool
     */
    public function setGroupDisturb(int $user_id, int $group_id, int $status)
    {

        if (!in_array($status, [0, 1])) return false;

        return UsersGroupMember::where('user_id', $user_id)->where('group_id', $group_id)->update(['not_disturb' => $status]);
    }

    /**
     * 获取聊天文件
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接受对象ID
     * @param int $type 聊天类型
     * @param int $page 分页
     * @param int $page_size 分页大小(默认15)
     * @return array
     */
    public function getChatFiles(int $user_id, int $receive_id, int $type, int $page, $page_size = 15)
    {
        $countSqlObj = UsersChatRecords::select();
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id', 'users_chat_records.send_time', 'users_chat_files.file_type', 'users_chat_files.file_suffix', 'users_chat_files.file_size', 'users_chat_files.original_name', 'users_chat_files.save_dir',
        ]);

        if ($type == 1) {//好友私信
            $where1 = [
                ['users_chat_records.msg_type', '=', 2],
                ['users_chat_records.user_id', '=', $user_id],
                ['users_chat_records.receive_id', '=', $receive_id]
            ];

            $where2 = [
                ['users_chat_records.msg_type', '=', 2],
                ['users_chat_records.user_id', '=', $receive_id],
                ['users_chat_records.receive_id', '=', $user_id]
            ];

            $countSqlObj->where($where1)->orWhere($where2);
            $rowsSqlObj->where($where1)->orWhere($where2);
        } else {//群聊消息
            $countSqlObj->where('users_chat_records.msg_type', 2);
            $rowsSqlObj->where('users_chat_records.msg_type', 2);

            $countSqlObj->where('users_chat_records.receive_id', $receive_id);
            $rowsSqlObj->where('users_chat_records.receive_id', $receive_id);
        }

        $countSqlObj->join('users_chat_files', function ($join) {
            $join->on('users_chat_files.id', '=', 'users_chat_records.file_id')->whereIn('users_chat_files.file_type', [2, 3]);
        });

        $rowsSqlObj->join('users_chat_files', function ($join) {
            $join->on('users_chat_files.id', '=', 'users_chat_records.file_id')->whereIn('users_chat_files.file_type', [2, 3]);
        });

        $count = $countSqlObj->count();
        $rows = [];
        if ($count > 0) {
            $rows = $rowsSqlObj->forPage($page, $page_size)->orderBy('users_chat_records.id', 'desc')->get()->toArray();
        }

        return $this->packData($rows, $count, $page, $page_size);
    }

    /**
     * 更新群聊头像
     *
     * @param int $group_id 群聊ID
     * @return bool
     */
    public function updateGroupAvatar(int $group_id)
    {
        $members = UsersGroupMember::leftJoin('users', 'users.id', '=', 'users_group_member.user_id')->where([
            ['users_group_member.group_id', '=', $group_id],
            ['users_group_member.status', '=', 0],
        ])->orderBy('users_group_member.created_at', 'asc')->limit(9)->get(['users.avatarurl', 'users.id'])->toArray();

        $images = $user_ids = [];
        foreach ($members as $member) {
            $images[] = $member['avatarurl'];
            $user_ids[] = shortCode($member['id']);
        }

        try {
            $object = new ImageCompose(array_filter($images));
            $object->compose();

            $save_dir = config('filesystems.disks.uploads.root');
            $path = 'avatar/' . date('Ymd') . '/' . implode('-', $user_ids) . '.png';
            $isTrue = $object->saveImage($save_dir . '/' . $path);
            unset($object);
            unset($members);

            if (!$isTrue) {
                return false;
            }

            UsersGroup::where('id', $group_id)->update(['avatarurl' => getFileUrl($path)]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
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

            'users_chat_records.file_id',
            'users_chat_files.user_id as send_user_id',
            'users_chat_files.flie_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name',

            'users.nickname',
            'users.avatarurl as avatar',
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
            $rowsSqlObj->whereIn('users_chat_records.msg_type', [1, 2]);
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
     * @param int $find_type 搜索消息类型(0:全部信息 1:图片信息 2:文件信息)
     * @param string $keywords 搜索关键词
     * @param int $record_id 上一次记录ID数
     * @param int $limit 分页大小
     * @return array
     */
    public function searchChatRecords(int $user_id, int $receive_id, int $source, int $find_type, string $keywords, int $record_id, $limit = 30)
    {
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',

            'users_chat_records.file_id',
            'users_chat_files.user_id as send_user_id',
            'users_chat_files.flie_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name',

            'users.nickname',
            'users.avatarurl as avatar',
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
            $rowsSqlObj->whereIn('users_chat_records.msg_type', [1, 2]);
        }

        $rowsSqlObj->where('users_chat_records.content', 'like', "%{$keywords}%");

        return $rowsSqlObj->orderBy('users_chat_records.id', 'desc')->limit(30)->get()->toArray();
    }

    /**
     * 获取聊天记录
     *
     * @param int $user_id 用户ID
     * @param int $receive_id 接收者ID(用户ID或群聊接收ID)
     * @param int $source 聊天来源（1:私信 2:群聊）
     * @param int $record_id 上一次记录ID数
     * @param int $limit 分页大小
     * @return mixed
     */
    public function getChatsRecords(int $user_id, int $receive_id, int $source, int $record_id, int $limit)
    {
        $rowsSqlObj = UsersChatRecords::select([
            'users_chat_records.id',
            'users_chat_records.source',
            'users_chat_records.msg_type',
            'users_chat_records.user_id',
            'users_chat_records.receive_id',
            'users_chat_records.content',
            'users_chat_records.send_time',

            'users_chat_records.file_id',
            'users_chat_files.flie_source',
            'users_chat_files.file_type',
            'users_chat_files.file_suffix',
            'users_chat_files.file_size',
            'users_chat_files.save_dir',
            'users_chat_files.original_name as file_original_name',

            'users.nickname',
            'users.avatarurl as avatar',
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

        $rowsSqlObj->orderBy('users_chat_records.id', 'desc');

        return $rowsSqlObj->limit($limit)->get()->toArray();
    }
}
