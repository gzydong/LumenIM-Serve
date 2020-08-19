<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MobileInfo;
use Illuminate\Http\Request;
use App\Models\{User, UsersFriends};
use App\Logic\{UsersLogic, FriendsLogic, ChatLogic};
use App\Helpers\Cache\CacheHelper;
use App\Helpers\SendEmailCode;
use App\Facades\SocketResourceHandle;

class UsersController extends CController
{
    public $request;
    public $friendsLogic;

    public function __construct(Request $request, FriendsLogic $friendsLogic)
    {
        $this->request = $request;
        $this->friendsLogic = $friendsLogic;
    }

    /**
     * 获取我的信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDetail()
    {
        $userInfo = $this->getUser(true);
        return $this->ajaxSuccess('success', [
            'mobile' => $userInfo['mobile'],
            'nickname' => $userInfo['nickname'],
            'avatar' => $userInfo['avatar'],
            'motto' => $userInfo['motto'],
            'email' => $userInfo['email'],
            'gender' => $userInfo['gender']
        ]);
    }

    /**
     * 用户相关设置
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSetting()
    {
        $userInfo = $this->getUser();

        return $this->ajaxSuccess('success', [
            'user_info' => [
                'uid' => $userInfo->id,
                'nickname' => $userInfo->nickname,
                'avatar' => $userInfo->avatar,
                'motto' => $userInfo->motto,
                'gender' => $userInfo->gender,
            ],
            'setting' => [
                'theme_mode' => '',
                'theme_bag_img' => '',
                'theme_color' => '',
                'notify_cue_tone' => '',
                'keyboard_event_notify' => '',
            ]
        ]);
    }

    /**
     * 获取我的好友列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFriends()
    {
        $rows = UsersFriends::getUserFriends($this->uid());

        array_walk($rows, function ($item) {
            $item['online'] = SocketResourceHandle::getUserFds($item['id']) ? 1 : 0;
        });

        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 编辑我的信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserDetail()
    {
        $params = ['nickname', 'avatar', 'motto', 'gender'];
        if (!$this->request->has($params) || !isInt($this->request->post('gender'), true)) {
            return $this->ajaxParamError();
        }

        $isTrue = User::where('id', $this->uid())->update($this->request->only($params));
        return $isTrue ? $this->ajaxSuccess('个人信息修改成功') : $this->ajaxError('个人信息修改失败');
    }

    /**
     * 修改我的密码
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserPassword(UsersLogic $usersLogic)
    {
        if (!$this->request->filled(['old_password', 'new_password'])) {
            return $this->ajaxParamError();
        }

        if (!isPassword($this->request->post('new_password'))) {
            return $this->ajaxParamError('新密码格式错误(8~16位字母加数字)');
        }

        [$isTrue, $message] = $usersLogic->userChagePassword($this->uid(), $this->request->post('old_password'), $this->request->post('new_password'));
        return $this->ajaxReturn($isTrue ? 200 : 305, $message);
    }

    /**
     * 修改用户头像
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editAvatar()
    {
        $avatar = $this->request->post('avatar', '');
        if (empty($avatar)) {
            return $this->ajaxParamError();
        }

        $isTrue = User::where('id', $this->uid())->update(['avatar' => $avatar]);

        return $isTrue ? $this->ajaxSuccess('头像修改成功') : $this->ajaxError('头像修改失败');
    }

    /**
     * 获取我的好友申请记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFriendApplyRecords()
    {
        $page = $this->request->get('page', 1);
        $page_size = $this->request->get('page_size', 10);

        $data = $this->friendsLogic->friendApplyRecords($this->uid(), $page, $page_size);
        CacheHelper::setFriendApplyUnreadNum($this->uid(), 1);
        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 发送添加好友申请
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendFriendApply()
    {
        $friend_id = $this->request->post('friend_id', 0);
        $remarks = $this->request->post('remarks', '');
        if (!isInt($friend_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendsLogic->addFriendApply($this->uid(), $friend_id, $remarks);

        //确认是否操作成功
        if (!$isTrue) {
            return $this->ajaxError('发送好友申请失败...');
        }

        //判断对方是否在线。如果在线发送消息通知
        if ($fd = SocketResourceHandle::getUserFds($friend_id)) {

        }

        CacheHelper::setFriendApplyUnreadNum($friend_id);
        return $this->ajaxReturn(200, '发送好友申请成功...');
    }

    /**
     * 处理好友的申请
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleFriendApply()
    {
        $apply_id = $this->request->post('apply_id', 0);
        $remarks = $this->request->post('remarks', '');
        if (!isInt($apply_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendsLogic->handleFriendApply($this->uid(), $apply_id, $remarks);
        //判断是否是同意添加好友
        if ($isTrue) {
            //... 推送处理消息
        }

        return $isTrue ? $this->ajaxSuccess('处理完成...') : $this->ajaxError('处理失败，请稍后再试...');
    }

    /**
     * 删除好友申请记录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFriendApply()
    {
        $apply_id = $this->request->post('apply_id', 0);
        if (!isInt($apply_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendsLogic->delFriendApply($this->uid(), $apply_id);

        return $isTrue ? $this->ajaxSuccess('删除成功...') : $this->ajaxError('删除失败...');
    }

    /**
     * 获取好友申请未读数
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApplyUnreadNum()
    {
        return $this->ajaxSuccess('success', [
            'unread_num' => CacheHelper::getFriendApplyUnreadNum($this->uid())
        ]);
    }

    /**
     * 编辑好友备注信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editFriendRemark()
    {
        $friend_id = $this->request->post('friend_id', 0);
        $remarks = $this->request->post('remarks', '');

        if (!isInt($friend_id) || empty($remarks)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendsLogic->editFriendRemark($this->uid(), $friend_id, $remarks);

        if ($isTrue) {
            CacheHelper::setFriendRemarkCache($this->uid(), $friend_id, $remarks);
        }

        return $isTrue ? $this->ajaxSuccess('备注修改成功...') : $this->ajaxError('备注修改失败，请稍后再试...');
    }

    /**
     * 获取指定用户信息
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUserInfo(UsersLogic $usersLogic)
    {
        $user_id = $this->request->post('user_id', 0);
        $mobile = $this->request->post('mobile', '');
        $where = [];

        if (isInt($user_id)) {
            $where['uid'] = $user_id;
        } else if (isMobile($mobile)) {
            $where['mobile'] = $mobile;
        } else {
            return $this->ajaxParamError();
        }

        if ($data = $usersLogic->searchUserInfo($where, $this->uid())) {
            return $this->ajaxSuccess('success', $data);
        }

        return $this->ajaxReturn(303, 'success', []);
    }

    /**
     * 获取用户群聊列表
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserGroups(UsersLogic $usersLogic)
    {
        $rows = $usersLogic->getUserChatGroups($this->uid());
        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 更换用户手机号
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserMobile(UsersLogic $usersLogic)
    {
        $sms_code = $this->request->post('sms_code', '');
        $mobile = $this->request->post('mobile', '');
        $password = $this->request->post('password', '');

        if (!isMobile($mobile)) {
            return $this->ajaxParamError('手机号格式不正确');
        }
        if (empty($sms_code)) {
            return $this->ajaxParamError('短信验证码不正确');
        }

        //  这里使用邮箱发送验证码，可自行根据业务替换
        $result = MobileInfo::info($mobile);
        if (!isset($result['email'])) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $sendEmailCode = new SendEmailCode();
        if (!$sendEmailCode->check(SendEmailCode::CHANGE_MOBILE, $result['email'], $sms_code)) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $uid = $this->uid();
        $user_password = User::where('id', $uid)->value('password');
        if (!$usersLogic->checkAccountPassword($password, $user_password)) {
            return $this->ajaxError('账号密码验证失败');
        }

        [$isTrue, $message] = $usersLogic->renewalUserMobile($this->uid(), $mobile);
        if ($isTrue) {
            $sendEmailCode->delCode(SendEmailCode::CHANGE_MOBILE, $result['email']);
        }

        return $isTrue ? $this->ajaxSuccess('手机号更换成功') : $this->ajaxError($message);
    }

    /**
     * 修改手机号发送验证码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMobileCode()
    {
        $mobile = $this->request->post('mobile', '');

        if (!isMobile($mobile)) {
            return $this->ajaxParamError('手机号格式不正确');
        }

        if (User::where('mobile', $mobile)->exists()) {
            return $this->ajaxError('手机号已被他人注册');
        }

        $result = MobileInfo::info($mobile);
        if (isset($result['email'])) {
            $sendEmailCode = new SendEmailCode();
            $sendEmailCode->send(SendEmailCode::CHANGE_MOBILE, '绑定手机', $result['email']);
        }

        return $this->ajaxSuccess('验证码发送成功...');
    }

    /**
     * 解除好友关系
     *
     * @param ChatLogic $chatLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFriend(ChatLogic $chatLogic)
    {
        $friend_id = $this->request->post('friend_id', 0);
        $user_id = $this->uid();
        if (!isInt($friend_id)) {
            return $this->ajaxParamError();
        }

        if (!$this->friendsLogic->removeFriend($user_id, $friend_id)) {
            return $this->ajaxError('解除好友失败...');
        }

        //删除好友会话列表
        $chatLogic->delChatList($user_id, $friend_id, 2);
        $chatLogic->delChatList($friend_id, $user_id, 2);

        return $this->ajaxSuccess('success');
    }

    /**
     * 发送绑定邮箱的验证码
     *
     * @param SendEmailCode $sendEmailCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendChangeEmailCode(SendEmailCode $sendEmailCode)
    {
        $email = $this->request->post('email', '');
        if (empty($email)) {
            return $this->ajaxParamError();
        }

        $isTrue = $sendEmailCode->send(SendEmailCode::CHANGE_EMAIL, '绑定邮箱', $email);
        if (!$isTrue) {
            return $this->ajaxError('验证码发送失败...');
        }

        return $this->ajaxSuccess('验证码发送成功...');
    }

    /**
     * 修改用户邮箱接口
     *
     * @param UsersLogic $usersLogic
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserEmail(UsersLogic $usersLogic)
    {
        $email = $this->request->post('email', '');
        $email_code = $this->request->post('email_code', '');
        $password = $this->request->post('password', '');

        if (empty($email) || empty($email_code) || empty($password)) {
            return $this->ajaxParamError();
        }

        $sendEmailCode = new SendEmailCode();
        if (!$sendEmailCode->check(SendEmailCode::CHANGE_EMAIL, $email, $email_code)) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $uid = $this->uid();
        $user_password = User::where('id', $uid)->value('password');
        if (!$usersLogic->checkAccountPassword($password, $user_password)) {
            return $this->ajaxError('账号密码验证失败');
        }

        $isTrue = User::where('id', $uid)->update(['email' => $email]);
        if ($isTrue) {
            $sendEmailCode->delCode(SendEmailCode::CHANGE_EMAIL, $email);
        }

        return $isTrue ? $this->ajaxSuccess('邮箱设置成功...') : $this->ajaxError('邮箱设置失败...');
    }
}
