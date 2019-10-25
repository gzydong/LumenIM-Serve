<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\ChatLogic;

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
        $group_name = $this->request->post('group_name','');/**群聊名称*/
        $uids = $this->request->post('uids','');            /**群聊用户*/

        if(empty($group_name) || empty($uids)){
            return $this->ajaxParamError();
        }

        $uids = array_filter(explode(',',$uids));
        if(!checkIds($uids)){
            return $this->ajaxParamError();
        }

        [$isTrue,$data] = $this->chatLogic->launchGroupChat($this->uid(),$group_name,array_unique($uids));
        if($isTrue){
            //群聊创建成功后需要创建聊天室并发送消息通知
            // ... 逻辑后期添加

            return $this->ajaxError('创建群聊成功...');
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
        $friend_id = $this->request->post('friend_id',0);

        if(!checkNumber($group_id) || !checkNumber($friend_id)){
            return $this->ajaxParamError();
        }

        $isTrue = $this->chatLogic->inviteFriendsGroupChat($group_id,$friend_id);
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
}
