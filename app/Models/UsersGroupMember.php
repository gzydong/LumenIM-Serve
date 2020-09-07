<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersGroupMember extends Model
{

    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_group_member';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 获取聊天群成员ID
     *
     * @param int $group_id 群聊ID
     * @return mixed
     */
    public static function getGroupMenberIds(int $group_id)
    {
        return UsersGroupMember::where('group_id', $group_id)->where('status', 0)->pluck('user_id')->toArray();
    }

    /**
     * 获取用户的群名片
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群ID
     * @return mixed
     */
    public static function visitCard(int $user_id, int $group_id)
    {
        return UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->value('visit_card');
    }
}
