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
    protected $fillable = ['group_id','user_id','group_name','group_profile','people_num','status','avatarurl','created_at'];

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
        return $this->hasMany(UsersGroupMember::class,'group_id','id');
    }

    /**
     * 判断群是否存在并且没有被解散
     *
     * @param int $group_id 群ID
     * @return bool
     */
    public static function checkGroupExist(int $group_id){
        return self::where('id',$group_id)->where('status',0)->exists() ? true : false;
    }

    /**
     * 判断用户是否是群成员
     *
     * @param int $group_id  群ID
     * @param int $user_id   用户ID
     * @return bool
     */
    public static function checkGroupMember(int $group_id,int $user_id){
        return UsersGroupMember::where('group_id',$group_id)->where('user_id',$user_id)->where('status',0)->exists() ? true :false;
    }
}
