<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use App\Logic\UsersLogic;
use App\Logic\ChatLogic;

class UsersController extends CController
{

    /**
     * 获取用户好友列表
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFriends(UsersLogic $usersLogic){
        $rows = $usersLogic->getUserFriends($this->uid());
        return $this->ajaxSuccess('success',$rows);
    }


    /**
     * 编辑用户昵称
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editNickname(Request $request){
        if(!$request->filled(['nickname'])){
            return $this->ajaxParamError('昵称不能为空');
        }

        [$isTrue,$message] = User::editNickname($this->uid(),$request->post('nickname'));
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
}
