<?php
namespace App\Http\Controllers\Proxy;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersGroup;
use Illuminate\Http\Request;
use App\Logic\UsersLogic;
use App\Facades\ChatService;

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
    public function launchGroupChat(){
        $uuid = $this->request->post('uuid',[]);
        $group_id = $this->request->post('group_id',0);
        $message = $this->request->post('message',[]);
        if(!$uuid){
            return $this->ajaxReturn(301,'用户ID不能为空');
        }

        if(!isInt($group_id)){
            return $this->ajaxReturn(301,'群聊ID不能为空');
        }

        foreach ($uuid as $uid) {
            app('SocketFdUtil')->bindUserGroupChat( $group_id,$uid);
        }

        if($message){
            app('SocketFdUtil')->sendResponseMessage('join_group',
                app('SocketFdUtil')->getRoomGroupName($group_id),
                $message
            );
        }

        return $this->ajaxReturn(200,'已发送消息...');
    }

    /**
     * 处理邀请好友入群事件
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteGroupMember(UsersLogic $usersLogic){
        $user_id  = $this->request->post('user_id',0);
        $group_id = $this->request->post('group_id',0);
        $members_id = $this->request->post('members_id',[]);

        $userInfo = $usersLogic->getUserInfo($user_id,['id','nickname']);
        $clientFds = app('SocketFdUtil')->getRoomGroupName($group_id);

        $users = [['id' => $userInfo->id, 'nickname' => $userInfo->nickname]];
        $joinFds = [];
        foreach ($members_id as $uid) {
            $joinFds = array_merge($joinFds,app('SocketFdUtil')->getUserFds($uid));
            app('SocketFdUtil')->bindUserGroupChat( $group_id,$uid);
        }

        //推送群聊消息
        app('SocketFdUtil')->sendResponseMessage('chat_message', $clientFds, ChatService::getChatMessage(0,$group_id,2,1,[
            'id' => null,
            'msg_type' => 3,
            'content' => array_merge($users, User::select('id', 'nickname')->whereIn('id', $members_id)->get()->toArray()),
        ]));

        app('SocketFdUtil')->sendResponseMessage('join_group', $joinFds, [
            'group_name'=>UsersGroup::where('id', $group_id)->value('group_name')
        ]);

        return $this->ajaxReturn(200,'success');
    }

    /**
     * 处理用户群聊事件
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeGroupMember(){
        $group_id = $this->request->post('group_id', 0);
        $member_id = $this->request->post('member_id', 0);
        $message = $this->request->post('message', []);

        //将用户移出聊天室
        app('SocketFdUtil')->clearGroupRoom($member_id, $group_id);

        if($message){
            app('SocketFdUtil')->sendResponseMessage('chat_message', app('SocketFdUtil')->getRoomGroupName($group_id), $message);
        }

        return $this->ajaxReturn(200,'success');
    }
}
