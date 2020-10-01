<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserChatList;
use App\Models\UserFriends;
use App\Services\FriendService;
use App\Services\UserService;
use App\Support\SendEmailCode;
use Illuminate\Http\Request;
use App\Cache\ApplyNumCache;
use App\Cache\FriendRemarkCache;

class UsersController extends CController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FriendService
     */
    protected $friendService;

    /**
     * @var UserService
     */
    protected $userService;

    public function __construct(Request $request, FriendService $friendService, UserService $userService)
    {
        $this->request = $request;
        $this->friendService = $friendService;
        $this->userService = $userService;
    }

    /**
     * 获取我的信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDetail()
    {
        $userInfo = $this->userService->findById($this->uid(), ['mobile', 'nickname', 'avatar', 'motto', 'email', 'gender']);
        return $this->ajaxSuccess('success', [
            'mobile' => $userInfo->mobile,
            'nickname' => $userInfo->nickname,
            'avatar' => $userInfo->avatar,
            'motto' => $userInfo->motto,
            'email' => $userInfo->email,
            'gender' => $userInfo->gender
        ]);
    }

    /**
     * 用户相关设置
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSetting()
    {
        $userInfo = $this->userService->findById($this->uid(), ['id', 'nickname', 'avatar', 'motto', 'gender']);
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
        $rows = UserFriends::getUserFriends($this->uid());
        foreach ($rows as $k => $row) {
            $rows[$k]['online'] = app('client.manage')->isOnline($row['id']);
        }

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
        if (!$this->request->has($params) || !check_int($this->request->post('gender'), true)) {
            return $this->ajaxParamError();
        }

        $isTrue = User::where('id', $this->uid())->update($this->request->only($params));

        return $isTrue ? $this->ajaxSuccess('个人信息修改成功') : $this->ajaxError('个人信息修改失败');
    }

    /**
     * 修改我的密码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserPassword()
    {
        if (!$this->request->filled(['old_password', 'new_password'])) {
            return $this->ajaxParamError();
        }

        if (!check_password($this->request->post('new_password'))) {
            return $this->ajaxParamError('新密码格式错误(8~16位字母加数字)');
        }

        $userInfo = $this->userService->findById($this->uid(), ['id', 'password', 'mobile']);

        // 验证密码是否正确
        if (!$this->userService->checkPassword($userInfo->password, $this->request->post('old_password'))) {
            return $this->ajaxError('旧密码验证失败');
        }

        // 修改密码
        $isTrue = $this->userService->resetPassword($userInfo->mobile, $this->request->post('new_password'));
        return $isTrue ? $this->ajaxSuccess('密码修改成功...') : $this->ajaxError('密码修改失败...');
    }

    /**
     * 修改用户头像
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editAvatar()
    {
        $avatar = $this->request->post('avatar');
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
        $user_id = $this->uid();

        $data = $this->friendService->findApplyRecords($user_id, $page, $page_size);

        // 清空好友申请未读数
        ApplyNumCache::del($user_id);


        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 发送添加好友申请
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendFriendApply()
    {
        $friend_id = $this->request->post('friend_id');
        $remarks = $this->request->post('remarks', '');
        if (!check_int($friend_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendService->addFriendApply($this->uid(), $friend_id, $remarks);

        //确认是否操作成功
        if (!$isTrue) {
            return $this->ajaxError('发送好友申请失败...');
        }

        //判断对方是否在线。如果在线发送消息通知
        if (app('client.manage')->isOnline($friend_id)) {

        }
        // 好友申请未读消息数自增
        ApplyNumCache::setInc($friend_id);

        return $this->ajaxReturn(200, '发送好友申请成功...');
    }

    /**
     * 处理好友的申请
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleFriendApply()
    {
        $apply_id = $this->request->post('apply_id');
        $remarks = $this->request->post('remarks', '');
        if (!check_int($apply_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendService->handleFriendApply($this->uid(), $apply_id, $remarks);
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
        $apply_id = $this->request->post('apply_id');
        if (!check_int($apply_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->friendService->delFriendApply($this->uid(), $apply_id);

        return $isTrue ? $this->ajaxSuccess('删除成功...') : $this->ajaxError('删除失败...');
    }

    /**
     * 获取好友申请未读数
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApplyUnreadNum()
    {
        return $this->ajaxSuccess('success', [
            'unread_num' => ApplyNumCache::get($this->uid())
        ]);
    }

    /**
     * 编辑好友备注信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editFriendRemark()
    {
        $friend_id = $this->request->post('friend_id');
        $remarks = $this->request->post('remarks', '');

        if (!check_int($friend_id) || empty($remarks)) {
            return $this->ajaxParamError();
        }

        $user_id = $this->uid();

        $isTrue = $this->friendService->editFriendRemark($user_id, $friend_id, $remarks);
        if ($isTrue) {
            // 设置好友备注缓存
            FriendRemarkCache::set($user_id, $friend_id, $remarks);
        }

        return $isTrue ? $this->ajaxSuccess('备注修改成功...') : $this->ajaxError('备注修改失败，请稍后再试...');
    }

    /**
     * 获取指定用户信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUserInfo()
    {
        $user_id = $this->request->post('user_id', 0);
        $mobile = $this->request->post('mobile', '');
        $where = [];

        if (check_int($user_id)) {
            $where['uid'] = $user_id;
        } else if (check_mobile($mobile)) {
            $where['mobile'] = $mobile;
        } else {
            return $this->ajaxParamError();
        }

        if ($data = $this->userService->searchUserInfo($where, $this->uid())) {
            return $this->ajaxSuccess('success', $data);
        }

        return $this->ajaxReturn(303, 'success', []);
    }

    /**
     * 获取用户群聊列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserGroups()
    {
        $rows = $this->userService->getUserChatGroups($this->uid());
        return $this->ajaxSuccess('success', $rows);
    }

    /**
     * 更换用户手机号
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserMobile()
    {
        $sms_code = $this->request->post('sms_code', '');
        $mobile = $this->request->post('mobile', '');
        $password = $this->request->post('password', '');

        if (!check_mobile($mobile)) {
            return $this->ajaxParamError('手机号格式不正确');
        }
        if (empty($sms_code)) {
            return $this->ajaxParamError('短信验证码不正确');
        }

        if (!app('sms.code')->check('change_mobile', $mobile, $sms_code)) {
            return $this->ajaxParamError('验证码填写错误...');
        }

        $user_id = $this->uid();
        if (!$this->userService->checkPassword($password, User::where('id', $user_id)->value('password'))) {
            return $this->ajaxError('账号密码验证失败');
        }

        [$isTrue, $message] = $this->userService->changeMobile($user_id, $mobile);
        if ($isTrue) {
            app('sms.code')->delCode('change_mobile', $mobile);
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
        $user_id = $this->uid();
        if (in_array($user_id, [2054, 2055])) {
            return $this->ajaxParamError('测试账号不支持修改手机号');
        }

        $mobile = $this->request->post('mobile', '');
        if (!check_mobile($mobile)) {
            return $this->ajaxParamError('手机号格式不正确');
        }

        if (User::where('mobile', $mobile)->exists()) {
            return $this->ajaxError('手机号已被他人注册');
        }

        $data = ['is_debug' => true];
        [$isTrue, $result] = app('sms.code')->send('change_mobile', $mobile);
        if ($isTrue) {
            $data['sms_code'] = $result['data']['code'];
        } else {
            // ... 处理发送失败逻辑，当前默认发送成功
        }

        return $this->ajaxSuccess('验证码发送成功...', $data);
    }

    /**
     * 解除好友关系
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFriend()
    {
        $friend_id = $this->request->post('friend_id');
        $user_id = $this->uid();
        if (!check_int($friend_id)) {
            return $this->ajaxParamError();
        }

        if (!$this->friendService->removeFriend($user_id, $friend_id)) {
            return $this->ajaxError('解除好友失败...');
        }

        //删除好友会话列表
        UserChatList::delItem($user_id, $friend_id, 2);
        UserChatList::delItem($friend_id, $user_id, 2);

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
        $email = $this->request->post('email');
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function editUserEmail()
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
        if (!$this->userService->checkPassword($password, $user_password)) {
            return $this->ajaxError('账号密码验证失败');
        }

        $isTrue = User::where('id', $uid)->update(['email' => $email]);
        if ($isTrue) {
            $sendEmailCode->delCode(SendEmailCode::CHANGE_EMAIL, $email);
        }

        return $isTrue ? $this->ajaxSuccess('邮箱设置成功...') : $this->ajaxError('邮箱设置失败...');
    }
}
