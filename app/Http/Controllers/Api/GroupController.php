<?php

namespace App\Http\Controllers\Api;

use App\Services\GroupService;
use Illuminate\Http\Request;
use App\Models\{UserChatList, UserFriends};
use App\Models\Group\{UserGroup, UserGroupMember, UserGroupNotice};
use App\Helpers\RequestProxy;

/**
 * 聊天群组控制器
 *
 * Class GroupController
 * @package App\Http\Controllers\Api
 */
class GroupController extends CController
{
    /**
     * @var Request
     */
    public $request;

    /**
     * @var RequestProxy
     */
    public $requestProxy;

    /**
     * @var GroupService
     */
    public $groupService;

    public function __construct(Request $request, RequestProxy $requestProxy, GroupService $groupService)
    {
        $this->request = $request;
        $this->requestProxy = $requestProxy;
        $this->groupService = $groupService;
    }

    /**
     * 获取群信息接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $group_id = $this->request->get('group_id', 0);
        if (!check_int($group_id)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();
        $groupInfo = UserGroup::leftJoin('users', 'users.id', '=', 'users_group.user_id')
            ->where('users_group.id', $group_id)->where('users_group.status', 0)->first([
                'users_group.id', 'users_group.user_id',
                'users_group.group_name',
                'users_group.group_profile', 'users_group.avatar',
                'users_group.created_at',
                'users.nickname'
            ]);

        if (!$groupInfo) {
            return $this->ajaxSuccess('success', []);
        }

        $notice = UserGroupNotice::where('group_id', $group_id)->where('is_delete', 0)->orderBy('id', 'desc')->first(['title', 'content']);
        return $this->ajaxSuccess('success', [
            'group_id' => $groupInfo->id,
            'group_name' => $groupInfo->group_name,
            'group_profile' => $groupInfo->group_profile,
            'avatar' => $groupInfo->avatar,
            'created_at' => $groupInfo->created_at,
            'is_manager' => $groupInfo->user_id == $user_id,
            'manager_nickname' => $groupInfo->nickname,
            'visit_card' => UserGroupMember::visitCard($user_id, $group_id),
            'not_disturb' => UserChatList::where('uid', $user_id)->where('group_id', $group_id)->where('type', 2)->value('not_disturb') ?? 0,
            'notice' => $notice ? $notice->toArray() : []
        ]);
    }

    /**
     * 创建群组接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $params = $this->request->only(['group_name', 'group_profile', 'uids']);
        if (count($params) != 3 || empty($params['group_name']) || empty($params['uids'])) {
            return $this->ajaxParamError();
        }

        $friend_ids = array_filter(explode(',', $params['uids']));
        if (!check_ids($friend_ids)) {
            return $this->ajaxParamError();
        }

        [$isTrue, $data] = $this->groupService->create($this->uid(), [
            'name' => $params['group_name'],
            'avatar' => '',
            'profile' => $params['group_profile'],
        ], array_unique($friend_ids));

        if ($isTrue) {
            //群聊创建成功后需要创建聊天室并发送消息通知
            $this->requestProxy->send('proxy/event/group-notify', [
                'record_id' => $data['record_id']
            ]);

            return $this->ajaxSuccess('创建群聊成功...', [
                'group_id' => $data['group_id']
            ]);
        }

        return $this->ajaxError('创建群聊失败，请稍后再试...');
    }

    /**
     * 编辑群信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editDetail()
    {
        $params = $this->request->only(['group_id', 'group_name', 'group_profile', 'avatar']);
        if (count($params) != 4 || empty($params['group_name'])) {
            return $this->ajaxParamError();
        }

        $result = UserGroup::where('id', $params['group_id'])->where('user_id', $this->uid())->update([
            'group_name' => $params['group_name'],
            'group_profile' => $params['group_profile'],
            'avatar' => $params['avatar']
        ]);

        return $result ? $this->ajaxSuccess('信息修改成功...') : $this->ajaxError('信息修改失败...');
    }

    /**
     * 邀请好友加入群组接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function invite()
    {
        $group_id = $this->request->post('group_id', 0);
        $uids = array_filter(explode(',', $this->request->post('uids', '')));

        if (!check_int($group_id) || !check_ids($uids)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();
        [$isTrue, $record_id] = $this->groupService->invite($user_id, $group_id, array_unique($uids));
        if ($isTrue) {
            $this->requestProxy->send('proxy/event/group-notify', [
                'record_id' => $record_id
            ]);
        }

        return $isTrue ? $this->ajaxSuccess('好友已成功加入群聊...') : $this->ajaxError('邀请好友加入群聊失败...');
    }

    /**
     * 移除指定成员（管理员权限）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMembers()
    {
        $group_id = $this->request->post('group_id', 0);
        $member_ids = $this->request->post('members_ids', []);

        if (!check_int($group_id) || !check_ids($member_ids)) {
            return $this->ajaxParamError();
        }

        [$isTrue, $record_id] = $this->groupService->removeMember($group_id, $this->uid(), $member_ids);
        if ($isTrue) {
            $this->requestProxy->send('proxy/event/group-notify', [
                'record_id' => $record_id
            ]);
        }

        return $isTrue ? $this->ajaxSuccess('群聊用户已被移除..') : $this->ajaxError('群聊用户移除失败...');
    }

    /**
     * 解散群组接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismiss()
    {
        $group_id = $this->request->post('group_id', 0);
        if (!check_int($group_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->groupService->dismiss($group_id, $this->uid());
        if ($isTrue) {
            // ... 推送群消息
        }

        return $isTrue ? $this->ajaxSuccess('群聊已解散成功..') : $this->ajaxError('群聊解散失败...');
    }

    /**
     * 退出群组接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function secede()
    {
        $group_id = $this->request->post('group_id', 0);
        if (!check_int($group_id)) return $this->ajaxParamError();

        $user_id = $this->uid();
        [$isTrue, $record_id] = $this->groupService->quit($user_id, $group_id);
        if ($isTrue) {
            $this->requestProxy->send('proxy/event/group-notify', [
                'record_id' => $record_id
            ]);
        }

        return $isTrue ? $this->ajaxSuccess('已成功退出群聊...') : $this->ajaxError('退出群聊失败...');
    }

    /**
     * 设置用户群名片
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setGroupCard()
    {
        $group_id = $this->request->post('group_id', 0);
        $visit_card = $this->request->post('visit_card', '');

        if (!check_int($group_id) || empty($visit_card)) {
            return $this->ajaxParamError();
        }

        $isTrue = UserGroupMember::where('group_id', $group_id)->where('user_id', $this->uid())->where('status', 0)->update(['visit_card' => $visit_card]);
        return $isTrue ? $this->ajaxSuccess('设置成功') : $this->ajaxError('设置失败');
    }

    /**
     * 获取用户可邀请加入群组的好友列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInviteFriends()
    {
        $group_id = $this->request->get('group_id', 0);
        $friends = UserFriends::getUserFriends($this->uid());
        if ($group_id > 0 && $friends) {
            if ($ids = UserGroupMember::getGroupMenberIds($group_id)) {
                foreach ($friends as $k => $item) {
                    if (in_array($item['id'], $ids)) unset($friends[$k]);
                }
            }

            $friends = array_values($friends);
        }

        return $this->ajaxSuccess('success', $friends);
    }

    /**
     * 获取群组成员列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupMembers()
    {
        $user_id = $this->uid();
        $group_id = $this->request->get('group_id', 0);

        // 判断用户是否是群成员
        if (!UserGroup::isMember($group_id, $user_id)) {
            return $this->ajaxReturn(403, '非法操作');
        }

        $members = UserGroupMember::select([
            'users_group_member.id', 'users_group_member.group_owner as is_manager', 'users_group_member.visit_card',
            'users_group_member.user_id', 'users.avatar', 'users.nickname', 'users.gender',
            'users.motto',
        ])
            ->leftJoin('users', 'users.id', '=', 'users_group_member.user_id')
            ->where([
                ['users_group_member.group_id', '=', $group_id],
                ['users_group_member.status', '=', 0],
            ])->orderBy('is_manager', 'desc')->get()->toArray();

        return $this->ajaxSuccess('success', $members);
    }

    /**
     * 获取群组公告列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupNotices()
    {
        $user_id = $this->uid();
        $group_id = $this->request->get('group_id', 0);

        // 判断用户是否是群成员
        if (!UserGroup::isMember($group_id, $user_id)) {
            return $this->ajaxReturn(403, '非法操作');
        }

        $rows = UserGroupNotice::leftJoin('users', 'users.id', '=', 'users_group_notice.user_id')
            ->where([['users_group_notice.group_id', '=', $group_id], ['users_group_notice.is_delete', '=', 0]])
            ->orderBy('users_group_notice.id', 'desc')
            ->get([
                'users_group_notice.id',
                'users_group_notice.user_id',
                'users_group_notice.title',
                'users_group_notice.content',
                'users_group_notice.created_at',
                'users_group_notice.updated_at',
                'users.avatar', 'users.nickname',
            ])->toArray();

        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 创建/编辑群公告
     */
    public function editNotice()
    {
        $data = $this->request->only(['notice_id', 'group_id', 'title', 'content']);
        if (count($data) != 4 || !check_int($data['notice_id'], true)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!UserGroup::isManager($user_id, $data['group_id'])) {
            return $this->ajaxReturn(305, '非管理员禁止操作...');
        }

        // 判断是否是新增数据
        if (empty($data['notice_id'])) {
            $result = UserGroupNotice::create([
                'group_id' => $data['group_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                return $this->ajaxError('添加群公告信息失败...');
            }

            // ... 推送群消息
            return $this->ajaxSuccess('添加群公告信息成功...');
        }

        $result = UserGroupNotice::where('id', $data['notice_id'])->update(['title' => $data['title'], 'content' => $data['content'], 'updated_at' => date('Y-m-d H:i:s')]);
        return $result ? $this->ajaxSuccess('修改群公告信息成功...') : $this->ajaxError('修改群公告信息成功...');
    }

    /**
     * 删除群公告(软删除)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteNotice()
    {
        $group_id = $this->request->post('group_id', 0);
        $notice_id = $this->request->post('notice_id', 0);

        if (!check_int($group_id) || !check_int($notice_id)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!UserGroup::isManager($user_id, $group_id)) {
            return $this->ajaxReturn(305, 'fail');
        }

        $result = UserGroupNotice::where('id', $notice_id)->where('group_id', $group_id)->update(['is_delete' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
        return $result ? $this->ajaxError('删除公告失败...') : $this->ajaxSuccess('删除公告成功...');
    }
}
