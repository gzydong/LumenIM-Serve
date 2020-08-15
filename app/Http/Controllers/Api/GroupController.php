<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RequestProxy;
use App\Logic\GroupLogic;
use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use App\Helpers\Cache\CacheHelper;

use App\Models\{User, UsersGroup, UsersGroupMember, UsersGroupNotice};

/**
 * 聊天群组控制器
 *
 * Class GroupController
 * @package App\Http\Controllers\Api
 */
class GroupController extends CController
{
    public $request;
    public $requestProxy;
    public $groupLogic;

    public function __construct(Request $request, RequestProxy $requestProxy, GroupLogic $groupLogic)
    {
        $this->request = $request;
        $this->requestProxy = $requestProxy;
        $this->groupLogic = $groupLogic;
    }

    /**
     * 获取群信息接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $group_id = $this->request->get('group_id', 0);
        if (!isInt($group_id)) {
            return $this->ajaxParamError();
        }

        $data = $this->groupLogic->getGroupDetail($this->uid(), $group_id);
        return $this->ajaxSuccess('success', $data);
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

        $uids = array_filter(explode(',', $params['uids']));
        if (!checkIds($uids)) return $this->ajaxParamError();

        [$isTrue, $data] = $this->groupLogic->launchGroupChat($this->uid(), $params['group_name'], '', $params['group_profile'], array_unique($uids));

        if ($isTrue) {//群聊创建成功后需要创建聊天室并发送消息通知
            $this->requestProxy->send('proxy/event/launch-group-chat', [
                'uuid' => $uids,
                'group_id' => $data['group_info']['id'],
                'message' => [
                    'group_name' => $params['group_name']
                ]
            ]);

            return $this->ajaxSuccess('创建群聊成功...', $data);
        }

        return $this->ajaxError('创建群聊失败，请稍后再试...');
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

        if (!isInt($group_id) || !checkIds($uids)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();
        $isTrue = $this->groupLogic->inviteFriendsGroupChat($user_id, $group_id, array_unique($uids));
        if ($isTrue) {
            $this->requestProxy->send('proxy/event/invite-group-members', [
                'user_id' => $user_id,
                'group_id' => $group_id,
                'members_id' => $uids
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

        if (!isInt($group_id) || !checkIds($member_ids)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->groupLogic->removeGroupChat($group_id, $this->uid(), $member_ids);
        if ($isTrue) {
            // ... 推送群消息
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
        if (!isInt($group_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->groupLogic->dismissGroupChat($group_id, $this->uid());
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
        if (!isInt($group_id)) return $this->ajaxParamError();

        $user_id = $this->uid();
        $isTrue = $this->groupLogic->quitGroupChat($user_id, $group_id);
        if ($isTrue) {
            $user = $this->getUser();
            $this->requestProxy->send('proxy/event/remove-group-members', [
                'group_id' => $group_id,
                'member_id' => $user_id,
                'message' => [
                    'msg_type' => 3,
                    'group_notify' => [
                        'type' => 3,
                        'operate_user' => [
                            'id' => $user['id'],
                            'nickname' => $user['nickname']
                        ],
                        'users' => [
                            'id' => $user['id'],
                            'nickname' => $user['nickname']
                        ]
                    ]
                ]
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

        if (!isInt($group_id) || empty($visit_card)) {
            return $this->ajaxParamError();
        }

        $isTrue = UsersGroupMember::where('group_id', $group_id)->where('user_id', $this->uid())->where('status', 0)->update(['visit_card' => $visit_card]);
        if ($isTrue) {
            $user = $this->getUser();
            CacheHelper::setUserGroupVisitCard($group_id, $this->uid(), [
                'avatar' => $user->avatar,
                'nickname' => $user->nickname,
                'visit_card' => $visit_card
            ]);
        }

        return $isTrue ? $this->ajaxSuccess('设置成功') : $this->ajaxError('设置失败');
    }

    /**
     * 获取用户可邀请加入群组的好友列表
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInviteFriends(UsersLogic $usersLogic)
    {
        $group_id = $this->request->get('group_id', 0);
        $friends = $usersLogic->getUserFriends($this->uid());

        array_walk($friends, function (&$item) {
            $item = (array)$item;
        });

        if ($group_id > 0) {
            $ids = UsersGroupMember::getGroupMenberIds($group_id);
            if ($friends && $ids) {
                foreach ($friends as $k => $item) {
                    if (in_array($item['id'], $ids)) {
                        unset($friends[$k]);
                    }
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
        if (!UsersGroup::checkGroupMember($group_id, $user_id)) {
            return $this->ajaxReturn(403, '非法操作');
        }

        $members = UsersGroupMember::select([
            'users_group_member.id', 'users_group_member.group_owner as is_manager', 'users_group_member.visit_card',
            'users_group_member.user_id', 'users.avatar', 'users.nickname', 'users.gender',
            'users.motto',
        ])
            ->leftJoin('users', 'users.id', '=', 'users_group_member.user_id')
            ->where([
                ['users_group_member.group_id', '=', $group_id],
                ['users_group_member.status', '=', 0],
            ])->get()->toArray();

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
        if (!UsersGroup::checkGroupMember($group_id, $user_id)) {
            return $this->ajaxReturn(403, '非法操作');
        }

        $rows = UsersGroupNotice::leftJoin('users', 'users.id', '=', 'users_group_notice.user_id')
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
        if (count($data) != 4 || !isInt($data['notice_id'], true)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!UsersGroup::where('id', $data['group_id'])->where('user_id', $user_id)->where('status', 0)->exists()) {
            return $this->ajaxReturn(305, 'fail');
        }

        // 判断是否是新增数据
        if (empty($data['notice_id'])) {
            $result = UsersGroupNotice::create([
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

        $result = UsersGroupNotice::where('id', $data['notice_id'])->update(['title' => $data['title'], 'content' => $data['content'], 'updated_at' => date('Y-m-d H:i:s')]);
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

        if(!isInt($group_id) || !isInt($notice_id)){
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();

        // 判断用户是否是管理员
        if (!UsersGroup::where('id', $group_id)->where('user_id', $user_id)->where('status', 0)->exists()) {
            return $this->ajaxReturn(305, 'fail');
        }

        $result = UsersGroupNotice::where('id', $notice_id)->where('group_id', $group_id)->update(['is_delete' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
        return $result ? $this->ajaxError('删除公告失败...') : $this->ajaxSuccess('删除公告成功...');
    }
}
