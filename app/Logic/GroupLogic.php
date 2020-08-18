<?php

namespace App\Logic;

use App\Helpers\Cache\CacheHelper;
use App\Models\{
    UsersChatList,
    UsersChatRecords,
    UsersChatRecordsGroupNotify,
    UsersGroup,
    UsersGroupMember
};

use Illuminate\Support\Facades\DB;

class GroupLogic extends Logic
{

    /**
     * 创建群聊
     *
     * @param int $user_id 用户ID
     * @param string $group_name 群聊名称
     * @param string $group_avatar 群聊头像
     * @param string $group_profile 群聊用户ID(不包括群成员)
     * @param array $friends
     * @return array
     */
    public function createGroupChat(int $user_id, string $group_name, string $group_avatar, string $group_profile, $friends = [])
    {
        $friends[] = $user_id;
        $groupMember = [];
        $chatList = [];

        DB::beginTransaction();
        try {
            $insRes = UsersGroup::create([
                'user_id' => $user_id,
                'group_name' => $group_name,
                'avatar' => $group_avatar,
                'group_profile' => $group_profile,
                'people_num' => count($friends),
                'status' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$insRes) throw new \Exception('创建群失败');

            foreach ($friends as $k => $uid) {
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

            $result = UsersChatRecords::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $insRes->id,
                'send_time' => date('Y-m-d H:i;s')
            ]);

            if (!$result) throw new \Exception('创建群成员的聊天列表失败');

            UsersChatRecordsGroupNotify::create([
                'record_id' => $result->id,
                'type' => 1,
                'operate_user_id' => $user_id,
                'user_ids' => implode(',', $friends)
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, 0];
        }

        // 设置群聊消息缓存
        CacheHelper::setLastChatCache(['send_time' => date('Y-m-d H:i:s'), 'text' => '入群通知'], $insRes->id, 0);

        return [true, ['record_id' => $result->id, 'group_id' => $insRes->id]];
    }

    /**
     * 邀请好友加入群聊
     *
     * @param int $user_id 用户ID
     * @param int $group_id 聊天群ID
     * @param array $uids 被邀请的用户ID
     * @return array
     */
    public function inviteFriendsGroupChat(int $user_id, int $group_id, $uids = [])
    {
        $info = UsersGroupMember::select(['id', 'status'])->where('group_id', $group_id)->where('user_id', $user_id)->first();

        //判断主动邀请方是否属于聊天群成员
        if (!$info && $info->status == 1) return [false, 0];

        if (empty($uids)) return [false, 0];

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
                $insertArr1[] = ['type' => 2, 'uid' => $uid, 'friend_id' => 0, 'group_id' => $group_id, 'status' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
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

            $result = UsersChatRecords::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $group_id,
                'send_time' => date('Y-m-d H:i;s')
            ]);

            if (!$result) throw new \Exception('添加群通知记录失败1');

            $result2 = UsersChatRecordsGroupNotify::create([
                'record_id' => $result->id,
                'type' => 1,
                'operate_user_id' => $user_id,
                'user_ids' => implode(',', $uids)
            ]);

            if (!$result2) throw new \Exception('添加群通知记录失败2');

            UsersGroup::where('id', $group_id)->increment('people_num', count($uids));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, 0];
        }

        CacheHelper::setLastChatCache(['send_time' => date('Y-m-d H:i:s'), 'text' => '入群通知'], $group_id, 0);
        return [true, $result->id];
    }

    /**
     * 将指定的用户踢出群聊
     *
     * @param int $group_id 群ID
     * @param int $user_id 操作用户ID
     * @param array $member_ids 群成员ID
     * @return array
     */
    public function removeGroupChat(int $group_id, int $user_id, array $member_ids)
    {
        if (!UsersGroup::isManager($user_id, $group_id)) {
            return [false, 0];
        }

        DB::beginTransaction();
        try {
            //更新用户状态
            if (!UsersGroupMember::where('group_id', $group_id)->whereIn('user_id', $member_ids)->where('group_owner', 0)->update(['status' => 1])) {
                throw new \Exception('修改群成员状态失败');
            }

            $result = UsersChatRecords::create([
                'msg_type' => 3,
                'source' => 2,
                'user_id' => 0,
                'receive_id' => $group_id,
                'send_time' => date('Y-m-d H:i;s')
            ]);

            if (!$result) throw new \Exception('添加群通知记录失败1');

            $result2 = UsersChatRecordsGroupNotify::create([
                'record_id' => $result->id,
                'type' => 2,
                'operate_user_id' => $user_id,
                'user_ids' => implode(',', $member_ids)
            ]);

            if (!$result2) throw new \Exception('添加群通知记录失败2');

            UsersGroup::where('id', $group_id)->update(['people_num' => UsersGroupMember::where('group_id', $group_id)->where('group_owner', 0)->count()]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, 0];
        }


        return [true, $result->id];
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
        if (!UsersGroup::isManager($user_id, $group_id)) {
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
     * 用户退出群聊
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群聊ID
     * @return array
     */
    public function quitGroupChat(int $user_id, int $group_id)
    {
        $record_id = 0;
        DB::beginTransaction();
        try {
            $res = UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('group_owner', 0)->update(['status' => 1]);
            if ($res) {
                UsersChatList::where('uid', $user_id)->where('type', 2)->where('group_id', $group_id)->update(['status' => 0]);

                $result = UsersChatRecords::create([
                    'msg_type' => 3,
                    'source' => 2,
                    'user_id' => 0,
                    'receive_id' => $group_id,
                    'content' => $user_id,
                    'send_time' => date('Y-m-d H:i;s')
                ]);

                if (!$result) throw new \Exception('添加群通知记录失败 : quitGroupChat');

                $result2 = UsersChatRecordsGroupNotify::create([
                    'record_id' => $result->id,
                    'type' => 3,
                    'operate_user_id' => $user_id,
                    'user_ids' => $user_id
                ]);

                if (!$result2) throw new \Exception('添加群通知记录失败2  : quitGroupChat');

                UsersGroup::where('id', $group_id)->update(['people_num' => UsersGroupMember::where('group_id', $group_id)->where('group_owner', 0)->count()]);

                $record_id = $result->id;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, 0];
        }

        return [true, $record_id];
    }
}
