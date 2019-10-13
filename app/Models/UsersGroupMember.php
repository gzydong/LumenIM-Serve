<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/10/13
 * Time: 11:36
 */

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
    protected $fillable = ['group_id','uid','group_owner','created_at'];


    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}