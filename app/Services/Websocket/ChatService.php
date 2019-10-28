<?php
namespace App\Services\Websocket;

use App\Facades\WebSocketHelper;
use App\Models\User;
use App\Models\UsersFriends;
use App\Models\UsersGroup;
use App\Models\UsersChatRecords;
use App\Models\UsersChatList;
use Illuminate\Support\Facades\DB;
use SwooleTW\Http\Websocket\Facades\Websocket;
use App\Helpers\Cache\CacheHelper;

class ChatService
{

    /**
     * 验证发送消息用户与接受消息用户之间是否存在好友或群聊关系
     *
     * @param array $receive_msg 接受的消息
     * @return bool
     */
    public function check(array $receive_msg){
        //判断用户是否存在
        if(!User::checkUserExist($receive_msg['sendUser'])){
            $receive_msg['textMessage'] = '非法操作！';
            Websocket::to(WebSocketHelper::getUserFds($receive_msg['sendUser']))
                ->emit('notify', $receive_msg);
            return false;
        }

        if($receive_msg['sourceType'] == 1){//私信
            //判断发送者和接受者是否是好友关系
            if(!UsersFriends::checkFriends($receive_msg['sendUser'],$receive_msg['receiveUser'])){
                $receive_msg['textMessage'] = '温馨提示:您当前与对方尚未成功好友！';
                Websocket::to(WebSocketHelper::getUserFds($receive_msg['sendUser']))
                    ->emit('notify', $receive_msg);
                return false;
            }
        }else if($receive_msg['sourceType'] == 2){//群聊
            //判断群聊是否存在及未解散
            if(!UsersGroup::checkGroupExist($receive_msg['receiveUser'])){
                $receive_msg['textMessage'] = '温馨提示:群聊房间不存在(或已被解散)';
                Websocket::to(WebSocketHelper::getUserFds($receive_msg['sendUser']))
                    ->emit('notify', $receive_msg);
                return false;
            }

            //判断是否属于群成员
            if(!UsersGroup::checkGroupMember($receive_msg['receiveUser'],$receive_msg['sendUser'])){
                $receive_msg['textMessage'] = '温馨提示:您还没有加入该聊天群';
                Websocket::to(WebSocketHelper::getUserFds($receive_msg['sendUser']))
                    ->emit('notify', $receive_msg);
                return false;
            }
        }

        return true;
    }

    /**
     * 保存聊天记录
     *
     * @param array $receive_msg 聊天数据
     * @return bool
     */
    public static function saveChatRecord(array $receive_msg){
        if(!in_array($receive_msg['sourceType'],[1,2])){
            return true;
        }

        DB::beginTransaction();
        try{
            $recordRes = UsersChatRecords::create([
                'source'=>$receive_msg['sourceType'],
                'msg_type'=>$receive_msg['msgType'],
                'user_id'=>$receive_msg['sendUser'],
                'receive_id'=>$receive_msg['receiveUser'],
                'text_msg'=>$receive_msg['textMessage'],
                'send_time'=>$receive_msg['send_time'],
            ]);

            if(!$recordRes){
                throw new \Exception('聊天记录插入失败');
            }

            if($receive_msg['sourceType'] == 1){
                //创建好友的聊天列表
                $info2 = UsersChatList::where('type',1)->where('uid',$receive_msg['receiveUser'])->where('friend_id',$receive_msg['sendUser'])->first();
                if($info2){
                    if($info2->status == 0){
                        $info2->status = 1;
                        $info2->save();
                    }
                }else{
                    UsersChatList::create(['type'=>1,'uid'=>$receive_msg['receiveUser'],'friend_id'=>$receive_msg['sendUser'],'status'=>1,'created_at'=>date('Y-m-d H:i:s')]);
                }
            }else if($receive_msg['sourceType'] == 2){//群聊

            }

            CacheHelper::setFriendsChatCache($receive_msg['sendUser'],$receive_msg['receiveUser'],$receive_msg['textMessage']);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return false;
        }

        unset($receive_msg);
        return true;
    }
}
