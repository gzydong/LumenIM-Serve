<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Logic\ChatLogic;

class ChatController extends CController
{

    /**
     * 获取用户聊天列表
     *
     * @param ChatLogic $chatLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatList(ChatLogic $chatLogic){
        $rows = $chatLogic->getUserChatList($this->uid());
        return $this->ajaxSuccess('success',$rows);
    }

    /**
     * 获取私信或群聊的聊天记录
     *
     * @param Request $request
     * @param ChatLogic $chatLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatRecords(Request $request,ChatLogic $chatLogic){
        $record_id  = $request->get('record_id',0);
        $receive_id = $request->get('receive_id',0);
        $type       = $request->get('type',1);

        if(!checkNumber($record_id) || $record_id < 0){
            return $this->ajaxParamError();
        }

        if(!checkNumber($receive_id) || $receive_id < 0){
            return $this->ajaxParamError();
        }

        if(!in_array($type,[1,2])){
            return $this->ajaxParamError();
        }

        if($type == 1){
            $data = $chatLogic->getPrivateChatInfos($record_id,$this->uid(),$receive_id);
        }else{
            $data = $chatLogic->getGroupChatInfos($record_id,$receive_id,$this->uid());
        }

        return $this->ajaxSuccess('success',$data);
    }
}
