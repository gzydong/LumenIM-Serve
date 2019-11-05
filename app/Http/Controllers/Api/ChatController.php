<?php
namespace App\Http\Controllers\Api;

use App\Models\UsersGroup;
use App\Models\UsersGroupMember;
use Illuminate\Http\Request;
use App\Logic\ChatLogic;
use App\Facades\WebSocketHelper;
use App\Logic\UsersLogic;

class ChatController extends CController
{
    public $request;
    public $chatLogic;

    public function __construct(Request $request,ChatLogic $chatLogic)
    {
        $this->request = $request;
        $this->chatLogic = $chatLogic;
    }

    /**
     * 获取用户聊天列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatList(){
        $rows = $this->chatLogic->getUserChatList($this->uid());
        return $this->ajaxSuccess('success',$rows);
    }

    /**
     * 获取私信或群聊的聊天记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatRecords(){
        $record_id  = $this->request->get('record_id',0);
        $receive_id = $this->request->get('receive_id',0);
        $type       = $this->request->get('type',1);
        $page_size  = 20;
        if(!checkNumber($record_id) || $record_id < 0){
            return $this->ajaxParamError();
        }

        if(!checkNumber($receive_id) || $receive_id < 0){
            return $this->ajaxParamError();
        }

        if(!in_array($type,[1,2])){
            return $this->ajaxParamError();
        }

        $uid = $this->uid();
        if($type == 1){
            $data = $this->chatLogic->getPrivateChatInfos($record_id,$uid,$receive_id,$page_size);
        }else{
            $data = $this->chatLogic->getGroupChatInfos($record_id,$receive_id,$uid,$page_size);
        }

        if(count($data['rows']) > 0){
            $data['rows'] = array_map(function ($item) use ($uid){
                $item['float'] = ($item['user_id'] == $uid) ? 'right' : 'left';
                return $item;
            },$data['rows']);
        }

        $data['page_size'] = $page_size;

        return $this->ajaxSuccess('success',$data);
    }

    /**
     * 创建群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function launchGroupChat(){
        $group_name = $this->request->post('group_name','');
        $group_profile = $this->request->post('group_profile','');
        $uids = $this->request->post('uids','');

        if(empty($group_name) || empty($uids)){
            return $this->ajaxParamError();
        }

        $uids = array_filter(explode(',',$uids));
        if(!checkIds($uids)){
            return $this->ajaxParamError();
        }

        [$isTrue,$data] = $this->chatLogic->launchGroupChat($this->uid(),$group_name,$group_profile,array_unique($uids));
        if($isTrue){//群聊创建成功后需要创建聊天室并发送消息通知
            $fids = [];
            foreach ($data['uids'] as $uuid){
                WebSocketHelper::bindUserGroupChat($uuid,$data['group_info']['id']);
                if($ufds = WebSocketHelper::getUserFds($uuid)){
                    $fids = array_merge($fids,$ufds);
                }
            }

            if($fids){
                $group_info = $data['group_info'];
                WebSocketHelper::sendResponseMessage('join_group',$fids,['id'=>$group_info->id,'group_name'=>$group_info->group_name,'people_num'=>$group_info->people_num,'avatarurl'=>$group_info->avatarurl]);
            }

            return $this->ajaxSuccess('创建群聊成功...');
        }

        return $this->ajaxError('创建群聊失败，请稍后再试...');
    }

    /**
     * 邀请好友加入群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteGroupChat(){
        $group_id = $this->request->post('group_id',0);
        $uids = array_filter(explode(',',$this->request->post('uids','')));

        if(!checkNumber($group_id) || !checkIds($uids)){
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->inviteFriendsGroupChat($this->uid(),$group_id,$uids);
        if($isTrue){
            $fids = [];
            foreach ($uids as $uuid){
                WebSocketHelper::bindUserGroupChat($uuid,$group_id);
                if($ufds = WebSocketHelper::getUserFds($uuid)){
                    $fids = array_merge($fids,$ufds);
                }
            }

            $groupInfo = UsersGroup::select(['id','group_name','people_num','avatarurl'])->where('id',$group_id)->first()->toArray();
            WebSocketHelper::sendResponseMessage('join_group',$fids,$groupInfo);
        }

        return $isTrue ? $this->ajaxSuccess('好友已成功加入群聊...') : $this->ajaxError('邀请好友加入群聊失败...');
    }

    /**
     * 用户踢出群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeGroupChat(){
        $group_id = $this->request->post('group_id',0);
        $member_id = $this->request->post('member_id',0);

        if(!checkNumber($group_id) || !checkNumber($member_id)){
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->removeGroupChat($group_id,$this->uid(),$member_id);

        return $isTrue ? $this->ajaxSuccess('群聊用户已被移除..') : $this->ajaxError('群聊用户移除失败...');
    }

    /**
     * 解散群聊
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismissGroupChat(){
        $group_id = $this->request->post('group_id',0);
        if(!checkNumber($group_id)){
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->dismissGroupChat($group_id,$this->uid());
        return $isTrue ? $this->ajaxSuccess('群聊已解散成功..') : $this->ajaxError('群聊解散失败...');
    }

    /**
     * 创建用户聊天列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createChatList(){
        $type = $this->request->post('type',1);//创建的类型
        $receive_id = $this->request->post('receive_id',0);//接收者ID

        if(!in_array($type,[1,2]) || !checkNumber($receive_id) || $receive_id <= 0){
            return $this->ajaxParamError();
        }

        $id = $this->chatLogic->createChatList($this->uid(),$receive_id,$type);
        return $id ? $this->ajaxSuccess('创建成功...',['list_id'=>$id]) : $this->ajaxError('创建失败...');
    }

    /**
     * 获取聊天群信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupDetail(){
        $group_id = $this->request->get('group_id',0);
        if(!checkNumber($group_id) || $group_id <= 0){
            return $this->ajaxParamError();
        }

        $data = $this->chatLogic->getGroupDetail($this->uid(),$group_id);
        return $this->ajaxSuccess('success',$data);
    }


    /**
     * 获取用户聊天好友
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatMember(UsersLogic $usersLogic){
        $group_id = $this->request->get('group_id',0);
        $firends = $usersLogic->getUserFriends($this->uid());
        if($group_id > 0){
            $ids = UsersGroupMember::getGroupMenberIds($group_id);
            if($firends && $ids){
                foreach ($firends as $k=>$item){
                    if(in_array($item->id,$ids)){
                        unset($firends[$k]);
                    }
                }
            }
        }

        return $this->ajaxSuccess('success',$firends);
    }
}
