<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RequestProxy;
use App\Logic\GroupLogic;
use App\Logic\UsersLogic;
use Illuminate\Http\Request;
use App\Helpers\Cache\CacheHelper;

use App\Models\{
    User,
    UsersGroupMember
};

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
     * 获取群信息
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
     * 创建群聊
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
     * 邀请好友加入群聊
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
        $member_id = $this->request->post('member_id', 0);

        if (!isInt($group_id) || !isInt($member_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->groupLogic->removeGroupChat($group_id, $this->uid(), $member_id);
        if ($isTrue) {
            $user = $this->getUser();
            $this->requestProxy->send('proxy/event/remove-group-members', [
                'member_id' => $member_id,
                'group_id' => $group_id,
                'message' => [
                    'msg_type' => 4,
                    'content' => [
                        [
                            'id' => $user['id'],
                            'nickname' => $user['nickname']
                        ],
                        [
                            'id' => $member_id,
                            'nickname' => User::where('id', $member_id)->value('nickname')
                        ]
                    ],
                    'receive_user' => $group_id,
                    'send_user' => 0,
                    'send_time' => date('Y-m-d H:i:s'),
                    'source_type' => 2
                ]
            ]);
        }
        return $isTrue ? $this->ajaxSuccess('群聊用户已被移除..') : $this->ajaxError('群聊用户移除失败...');
    }

    /**
     * 解散群聊
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
     * 退出群聊
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
     * 获取群聊成员
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupMember(UsersLogic $usersLogic)
    {
        $group_id = $this->request->get('group_id', 0);
        $friends = $usersLogic->getUserFriends($this->uid());
        if ($group_id > 0) {
            $ids = UsersGroupMember::getGroupMenberIds($group_id);
            if ($friends && $ids) {
                foreach ($friends as $k => $item) {
                    if (in_array($item->id, $ids)) {
                        unset($friends[$k]);
                    }
                }
            }
        }

        return $this->ajaxSuccess('success', $friends);
    }
}
