<?php

namespace App\Models\Group;

use App\Models\BaseModel;

class UserGroup extends BaseModel
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_group';

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
     * 获取群聊成员
     */
    public function members()
    {
        return $this->hasMany(UserGroupMember::class, 'group_id', 'id');
    }

    /**
     * 判断用户是否是管理员
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群ID
     * @return mixed
     */
    public static function isManager(int $user_id,int $group_id){
        return UserGroup::where('id', $group_id)->where('user_id', $user_id)->exists();
    }

    /**
     * 判断用户是否是群成员
     *
     * @param int $group_id 群ID
     * @param int $user_id 用户ID
     * @return bool
     */
    public static function isMember(int $group_id, int $user_id)
    {
        return UserGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('status', 0)->exists() ? true : false;
    }
}
