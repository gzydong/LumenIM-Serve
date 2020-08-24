<?php

namespace App\Http\Controllers\Proxy;

use App\Helpers\Socket\NotifyInterface;
use App\Http\Controllers\Controller;
use App\Models\ChatRecords;
use App\Models\ChatRecordsForward;
use App\Models\ChatRecordsInvite;
use App\Models\User;
use App\Models\UsersGroup;
use Illuminate\Http\Request;
use App\Facades\SocketResourceHandle;

class EventController extends Controller
{
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     *  邀请入群通知  踢出群聊通知  自动退出群聊
     */
    public function groupNotify()
    {
        $record_id = $this->request->post('record_id', 0);
        if (!isInt($record_id)) {
            return $this->ajaxReturn(301, '请求参数错误');
        }

        $recordInfo = ChatRecords::where('id', $record_id)->where('source', 2)->first([
            'id', 'msg_type', 'user_id', 'receive_id', 'created_at'
        ]);

        if (!$recordInfo) {
            return $this->ajaxReturn(305, 'fail');
        }

        $notifyInfo = ChatRecordsInvite::where('record_id', $record_id)->first([
            'record_id', 'type', 'operate_user_id', 'user_ids'
        ]);

        if (!$notifyInfo) {
            return $this->ajaxReturn(305, 'fail');
        }

        $userInfo = User::where('id', $notifyInfo->operate_user_id)->first(['nickname', 'id']);

        $membersIds = explode(',', $notifyInfo->user_ids);

        // 获取客户端列表
        $clientFds = SocketResourceHandle::getRoomGroupName($recordInfo->receive_id);
        $joinClientFds = [];

        if ($notifyInfo->type == 1) {//好友入群
            foreach ($membersIds as $member_id) {
                SocketResourceHandle::bindUserGroupChat($recordInfo->receive_id, $member_id);
                $joinClientFds = array_merge($joinClientFds, SocketResourceHandle::getUserFds($member_id));
            }
        } else if ($notifyInfo->type == 2 || $notifyInfo->type == 3) {//好友退群或被踢出群
            foreach ($membersIds as $member_id) {
                SocketResourceHandle::clearGroupRoom($member_id, $recordInfo->receive_id);
            }
        }

        //推送群聊消息
        SocketResourceHandle::response('chat_message', $clientFds, [
            'send_user' => 0,
            'receive_user' => $recordInfo->receive_id,
            'source_type' => 2,
            'data' => NotifyInterface::formatTalkMsg([
                "id" => $recordInfo->id,
                "source" => 2,
                "msg_type" => 3,
                "user_id" => 0,
                "receive_id" => $recordInfo->receive_id,
                "invite" => [
                    'type' => $notifyInfo->type,
                    'operate_user' => ['id' => $userInfo->id, 'nickname' => $userInfo->nickname],
                    'users' => User::select('id', 'nickname')->whereIn('id', $membersIds)->get()->toArray()
                ],
                "created_at" => $recordInfo->created_at,
            ])
        ]);

        // 推送入群通知
        if ($notifyInfo->type == 1) {
            SocketResourceHandle::response('join_group',
                $joinClientFds,
                [
                    'group_name' => UsersGroup::where('id', $recordInfo->receive_id)->value('group_name')
                ]
            );
        }
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

        $records = ChatRecords::where('id', $record_id)->first(['id', 'source', 'user_id', 'receive_id']);
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

        SocketResourceHandle::response('revoke_records',
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

        $rows = ChatRecordsForward::leftJoin('users', 'users.id', '=', 'chat_records_forward.user_id')
            ->leftJoin('chat_records', 'chat_records.id', '=', 'chat_records_forward.record_id')
            ->whereIn('chat_records_forward.record_id', $records_id)
            ->get([
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

                'chat_records_forward.records_id',
                'chat_records_forward.text',
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

            SocketResourceHandle::response('chat_message', $client, [
                'send_user' => $records->user_id,
                'receive_user' => $records->receive_id,
                'source_type' => $records->source,
                'data' => NotifyInterface::formatTalkMsg([
                    'id' => $records->id,
                    'msg_type' => $records->msg_type,
                    'source' => $records->source,
                    'avatar' => $records->avatar,
                    'nickname' => $records->nickname,
                    "user_id" => $records->user_id,
                    "receive_id" => $records->receive_id,
                    "created_at" => $records->created_at,
                    "forward" => [
                        'num' => substr_count($records->records_id, ',') + 1,
                        'list' => json_decode($records->text, true) ?? []
                    ]
                ])
            ]);
        }

        return $this->ajaxReturn(200, 'success');
    }
}
