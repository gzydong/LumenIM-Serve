<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use App\Logic\UsersLogic;
use App\Logic\FriendsLogic;
use App\Facades\WebSocketHelper;

class UsersController extends CController
{

    /**
     * 获取用户个人信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDetail(){
        $userInfo = $this->getUser(true);

        return $this->ajaxSuccess('success',[
          'mobile'   => $userInfo['mobile'],
          'nickname' => $userInfo['nickname'],
          'avatarurl'=> $userInfo['avatarurl'],
          'motto'    => '生活需要梦想、需要坚持。只有不断提高自我，才会得到想要的生活...'
        ]);
    }

    /**
     * 获取用户好友列表
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFriends(UsersLogic $usersLogic){
        $rows = $usersLogic->getUserFriends($this->uid());
        if($rows){
            foreach ($rows as $k => $row){
                $rows[$k]->online = WebSocketHelper::getUserFds($row->id) ? 1 : 0;
            }
        }

        return $this->ajaxSuccess('success',$rows);
    }

    /**
     * 编辑用户信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserDetail(Request $request){
        if(!$request->has(['nickname','avatarurl','motto'])){
            return $this->ajaxParamError();
        }

        [$isTrue,$message] = User::editUserDetail($this->uid(),$request->only(['nickname','avatarurl','motto']));
        return $this->ajaxReturn($isTrue?200:305,$message);
    }

    /**
     * 修改用户密码
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request){
        if(!$request->filled(['old_password','new_password'])){
            return $this->ajaxParamError();
        }

        if(!isPassword($request->post('new_password'))){
            return $this->ajaxParamError('新密码格式错误(8~16位字母加数字)');
        }

        [$isTrue,$message] = User::changePassword($this->uid(),$request->post('old_password'),$request->post('new_password'));
        return $this->ajaxReturn($isTrue?200:305,$message);
    }

    /**
     * 修改用户头像
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editAvatar(Request $request){
        if(!$request->filled(['avatarurl'])){
            return $this->ajaxParamError();
        }

        [$isTrue,$message] = User::editHeadPortrait($this->uid(),$request->post('avatarurl'));
        return $this->ajaxReturn($isTrue?200:305,$message);
    }

    /**
     * 获取用户好友申请记录
     *
     * @param Request $request
     * @param FriendsLogic $friendsLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFriendApplyRecords(Request $request,FriendsLogic $friendsLogic){
        $data = $friendsLogic->friendApplyRecords($this->uid(),intval($request->get('page',1)));
        return $this->ajaxSuccess('success',$data);
    }

    /**
     * 发送添加好友申请
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendFriendApply(Request $request){
        $friend_id = $request->post('friend_id',0);
        $remarks   = $request->post('remarks','');
        if(!checkNumber($friend_id) || $friend_id <= 0){
            return $this->ajaxParamError();
        }

        $isTrue = FriendsLogic::addFriendApply($this->uid(),$friend_id,$remarks);

        //确认是否操作成功
        if(!$isTrue){
            return $this->ajaxError('发送好友申请失败...');
        }

        //判断对方是否在线。如果在线发送消息通知
        if($fd = WebSocketHelper::getUserFds($friend_id)){
            WebSocketHelper::sendResponseMessage('friend_apply',$fd,[]);
        }

        return $this->ajaxReturn(200,'发送好友申请成功...');
    }

    /**
     * 处理好友的申请
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleFriendApply(Request $request){
        $apply_id = $request->post('apply_id',0);
        $type     = $request->post('type',0);
        $remarks  = $request->post('remarks','');
        if(!checkNumber($apply_id) || $apply_id <= 0 || !in_array($type,[1,2])){
            return $this->ajaxParamError();
        }

        if($type == 2 && empty($remarks)){
            return $this->ajaxParamError('请填写拒绝的原因...');
        }

        $isTrue = FriendsLogic::handleFriendApply($this->uid(),$apply_id,$type,$remarks);
        return $isTrue ? $this->ajaxSuccess('处理完成...') : $this->ajaxError('处理失败，请稍后再试...');
    }

    /**
     * 编辑好友备注信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editFriendRemark(Request $request){
        $friend_id = $request->post('friend_id',0);
        $remarks   = $request->post('remarks','');

        if(!checkNumber($friend_id) || empty($remarks)){
            return $this->ajaxParamError();
        }

        $isTrue = FriendsLogic::editFriendRemark($this->uid(),$friend_id,$remarks);

        return $isTrue ? $this->ajaxSuccess('备注修改成功...') : $this->ajaxError('备注修改失败，请稍后再试...');
    }

    /**
     * 获取用户信息
     *
     * @param Request $request
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUserInfo(Request $request,UsersLogic $usersLogic){
        $user_id = $request->post('user_id',0);
        $mobile = $request->post('mobile','');
        $where = [];

        if(checkNumber($user_id) && $user_id > 0){
            $where['uid'] = $user_id;
        }else if(isMobile($mobile)){
            $where['mobile'] = $mobile;
        }else{
            return $this->ajaxParamError();
        }

        $data = $usersLogic->searchUserInfo($where,$this->uid());

        if($data){
            return $this->ajaxSuccess('success',$data);
        }

        return $this->ajaxReturn(303,'success',[]);
    }


    /**
     * 获取用户群聊列表
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserGroups(UsersLogic $usersLogic){
        $rows = $usersLogic->getUserChatGroups($this->uid());
        return $this->ajaxSuccess('success',$rows);
    }
}
