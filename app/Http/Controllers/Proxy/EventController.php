<?php

namespace App\Http\Controllers\Proxy;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersChatRecords;
use App\Models\UsersGroup;
use Illuminate\Http\Request;
use App\Logic\UsersLogic;
use App\Helpers\Socket\ChatService;
use App\Facades\SocketResourceHandle;

class EventController extends Controller
{
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 通知发送创建群聊消息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function launchGroupChat()
    {
        $uuid = $this->request->post('uuid', []);
        $group_id = $this->request->post('group_id', 0);
        $message = $this->request->post('message', []);
        if (!$uuid) {
            return $this->ajaxReturn(301, '用户ID不能为空');
        }

        if (!isInt($group_id)) {
            return $this->ajaxReturn(301, '群聊ID不能为空');
        }

        foreach ($uuid as $uid) {
            SocketResourceHandle::bindUserGroupChat($group_id, $uid);
        }

        if ($message) {
            SocketResourceHandle::responseResource('join_group',
                SocketResourceHandle::getRoomGroupName($group_id),
                $message
            );
        }

        return $this->ajaxReturn(200, '已发送消息...');
    }

    /**
     * 处理邀请好友入群事件
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteGroupMember(UsersLogic $usersLogic)
    {
        $user_id = $this->request->post('user_id', 0);
        $group_id = $this->request->post('group_id', 0);
        $members_id = $this->request->post('members_id', []);

        $userInfo = $usersLogic->getUserInfo($user_id, ['id', 'nickname']);
        $clientFds = SocketResourceHandle::getRoomGroupName($group_id);

        $joinFds = [];
        foreach ($members_id as $uid) {
            $joinFds = array_merge($joinFds, SocketResourceHandle::getUserFds($uid));
            SocketResourceHandle::bindUserGroupChat($group_id, $uid);
        }

        //推送群聊消息
        SocketResourceHandle::responseResource('chat_message', $clientFds, ChatService::getChatMessage(0, $group_id, 2, 1, [
            'id' => null,
            'msg_type' => 3,
            'group_notify' => [
                'type' => 1,
                'operate_user' => ['id' => $userInfo->id, 'nickname' => $userInfo->nickname],
                'users' => User::select('id', 'nickname')->whereIn('id', $members_id)->get()->toArray()
            ]
        ]));

        SocketResourceHandle::responseResource('join_group', $joinFds, [
            'group_name' => UsersGroup::where('id', $group_id)->value('group_name')
        ]);

        return $this->ajaxReturn(200, 'success');
    }

    /**
     * 处理用户群聊事件
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeGroupMember()
    {
        $group_id = $this->request->post('group_id', 0);
        $member_id = $this->request->post('member_id', 0);
        $message = $this->request->post('message', []);

        //将用户移出聊天室
        SocketResourceHandle::clearGroupRoom($member_id, $group_id);
        if ($message) {
            SocketResourceHandle::responseResource('chat_message',
                SocketResourceHandle::getRoomGroupName($group_id),
                ChatService::getChatMessage(0, $group_id, 2, 1, $message)
            );
        }

        return $this->ajaxReturn(200, 'success');
    }

    /**
     * 推送好友撤销消息事件
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeRecords()
    {
        $record_id = $this->request->post('record_id', 0);
        if (!isInt($record_id)) {
            return $this->ajaxReturn(301, '请求参数错误');
        }

        $records = UsersChatRecords::where('id', $record_id)->first(['id', 'source', 'user_id', 'receive_id']);
        if (!$records) {
            return $this->ajaxReturn(305, '数据不存在...');
        }

        if ($records->source == 1) {
            $client = array_merge(
                SocketResourceHandle::getUserFds($records->user_id),
                SocketResourceHandle::getUserFds($records->receive_id)
            );
        } else {
            $client = SocketResourceHandle::getRoomGroupName($records->receive_id);
        }

        SocketResourceHandle::responseResource('revoke_records',
            $client,
            [
                'record_id' => $records->id,
                'source' => $records->source,
                'user_id' => $records->user_id,
                'receive_id' => $records->receive_id,
            ]
        );
    }

    /**
     * 推送聊天记录转发消息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forwardChatRecords()
    {
        $records_id = $this->request->post('records_id', []);
        if (!$records_id) {
            return $this->ajaxReturn(301, '请求参数错误...');
        }

        $rows = UsersChatRecords::leftJoin('users_chat_records_forward as forward', 'forward.id', '=', 'users_chat_records.forward_id')
            ->leftJoin('users', 'users.id', '=', 'users_chat_records.user_id')
            ->whereIn('users_chat_records.id', $records_id)
            ->get([
                'users.avatar',
                'users.nickname',
                'users_chat_records.id',
                'users_chat_records.source',
                'users_chat_records.msg_type',
                'users_chat_records.user_id',
                'users_chat_records.receive_id',
                'users_chat_records.forward_id',
                'users_chat_records.send_time',
                'forward.text as forward_info',
                'forward.records_id as records_id',
            ]);


        foreach ($rows as $records) {
            if ($records->source == 1) {
                $client = array_merge(
                    SocketResourceHandle::getUserFds($records->user_id),
                    SocketResourceHandle::getUserFds($records->receive_id)
                );
            } else {
                $client = SocketResourceHandle::getRoomGroupName($records->receive_id);
            }

            SocketResourceHandle::responseResource('chat_message', $client,
                ChatService::getChatMessage(
                    $records->user_id,
                    $records->receive_id,
                    $records->source,
                    $records->msg_type,
                    [
                        'id' => $records->id,
                        'msg_type' => $records->msg_type,
                        'source' => $records->source,
                        'avatar' => $records->avatar,
                        'nickname' => $records->nickname,
                        'friend_remarks' => $records->source == 2 ? '临时名片' : '',//群名片

                        'forward_id' => $records->forward_id,
                        'forward_info' => [
                            'num' => substr_count($records->records_id, ',') + 1,
                            'list' => json_decode($records->forward_info, true)
                        ],

                        'send_time' => $records->send_time,
                    ]
                )
            );

            unset($forwardInfo);
        }

        return $this->ajaxReturn(200, 'success');
    }
}
