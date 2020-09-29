<?php

namespace App\Http\Controllers\Proxy;

use App\Http\Controllers\Controller;
use App\Models\Chat\{
    ChatRecords,
    ChatRecordsCode,
    ChatRecordsFile,
    ChatRecordsForward,
    ChatRecordsInvite
};
use App\Models\User;
use App\Models\Group\UserGroup;
use Illuminate\Http\Request;
use App\Helpers\PushMessageHelper;

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
        if (!check_int($record_id)) {
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
        $clientFds = app('room.manage')->getRoomGroupName($recordInfo->receive_id);
        $joinClientFds = [];

        if ($notifyInfo->type == 1) {//好友入群
            foreach ($membersIds as $member_id) {
                app('room.manage')->bindUserToRoom($recordInfo->receive_id, $member_id);

                $joinClientFds = array_merge(
                    $joinClientFds,
                    app('client.manage')->findUserIdFds($member_id)
                );
            }
        } else if ($notifyInfo->type == 2 || $notifyInfo->type == 3) {//好友退群或被踢出群
            foreach ($membersIds as $member_id) {
                app('room.manage')->removeRoomUser($recordInfo->receive_id, $member_id);
            }
        }

        //推送群聊消息
        PushMessageHelper::response('chat_message', $clientFds, [
            'send_user' => 0,
            'receive_user' => $recordInfo->receive_id,
            'source_type' => 2,
            'data' => PushMessageHelper::formatTalkMsg([
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
            PushMessageHelper::response('join_group',
                $joinClientFds,
                [
                    'group_name' => UserGroup::where('id', $recordInfo->receive_id)->value('group_name')
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
        if (!check_int($record_id)) {
            return $this->ajaxReturn(301, '请求参数错误');
        }

        $records = ChatRecords::where('id', $record_id)->first(['id', 'source', 'user_id', 'receive_id']);
        if (!$records) {
            return $this->ajaxReturn(305, '数据不存在...');
        }

        if ($records->source == 1) {
            $client = array_merge(
                app('client.manage')->findUserIdFds($records->user_id),
                app('client.manage')->findUserIdFds($records->receive_id)
            );
        } else {
            $client = app('room.manage')->getRoomGroupName($records->receive_id);
        }

        PushMessageHelper::response('revoke_records',
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
                    app('client.manage')->findUserIdFds($records->user_id),
                    app('client.manage')->findUserIdFds($records->receive_id)
                );
            } else {
                $client = app('room.manage')->getRoomGroupName($records->receive_id);
            }

            PushMessageHelper::response('chat_message', $client, [
                'send_user' => $records->user_id,
                'receive_user' => $records->receive_id,
                'source_type' => $records->source,
                'data' => PushMessageHelper::formatTalkMsg([
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

    /**
     * 根据消息ID推送客户端
     */
    public function pushTalkMessage()
    {
        $record_id = $this->request->post('record_id', 0);
        $info = ChatRecords::leftJoin('users', 'users.id', '=', 'chat_records.user_id')->where('chat_records.id', $record_id)->first([
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
        ]);

        if (!$info) {
            return $this->ajaxReturn(305, 'fail');
        }

        if ($info->source == 1) {
            $client = array_merge(
                app('client.manage')->findUserIdFds($info->user_id),
                app('client.manage')->findUserIdFds($info->receive_id)
            );
        } else {
            $client = app('room.manage')->getRoomGroupName($info->receive_id);
        }

        $file = [];
        $code_block = [];

        if ($info->msg_type == 2) {
            $file = ChatRecordsFile::where('record_id', $info->id)->first(['id', 'record_id', 'user_id', 'file_source', 'file_type', 'save_type', 'original_name', 'file_suffix', 'file_size', 'save_dir']);
            $file = $file ? $file->toArray() : [];
            if ($file) {
                $file['file_url'] = get_media_url($file['save_dir']);
            }
        } else if ($info->msg_type == 5) {
            $code_block = ChatRecordsCode::where('record_id', $info->id)->first(['record_id', 'code_lang', 'code']);
            $code_block = $code_block ? $code_block->toArray() : [];
        }

        PushMessageHelper::response('chat_message', $client, [
            'send_user' => $info->user_id,
            'receive_user' => $info->receive_id,
            'source_type' => $info->source,
            'data' => PushMessageHelper::formatTalkMsg([
                'id' => $info->id,
                'msg_type' => $info->msg_type,
                'source' => $info->source,
                'avatar' => $info->avatar,
                'nickname' => $info->nickname,
                "user_id" => $info->user_id,
                "receive_id" => $info->receive_id,
                "created_at" => $info->created_at,
                "file" => $file,
                "code_block" => $code_block
            ])
        ]);
    }
}
