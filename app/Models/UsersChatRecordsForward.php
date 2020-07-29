<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
class UsersChatRecordsForward extends Model
{

    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_chat_records_forward';

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
    protected $fillable = ['user_id','records_id','text','created_at'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
