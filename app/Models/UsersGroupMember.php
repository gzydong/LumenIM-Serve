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
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['group_id', 'user_id', 'group_owner', 'visit_card', 'created_at'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 获取聊天群成员ID
     * @param int $group_id 群聊ID
     * @return mixed
     */
    public static function getGroupMenberIds(int $group_id)
    {
        return UsersGroupMember::where('group_id', $group_id)->where('status', 0)->pluck('user_id')->toArray();
    }
}
