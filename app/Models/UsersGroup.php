<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersGroup extends Model
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
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['group_id', 'user_id', 'group_name', 'group_profile', 'people_num', 'status', 'avatar', 'created_at'];

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
        return $this->hasMany(UsersGroupMember::class, 'group_id', 'id');
    }

    /**
     * 判断用户是否是管理员
     *
     * @param int $user_id 用户ID
     * @param int $group_id 群ID
     * @return mixed
     */
    public static function isManager(int $user_id,int $group_id){
        return UsersGroup::where('id', $group_id)->where('user_id', $user_id)->exists();
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
        return UsersGroupMember::where('group_id', $group_id)->where('user_id', $user_id)->where('status', 0)->exists() ? true : false;
    }
}
