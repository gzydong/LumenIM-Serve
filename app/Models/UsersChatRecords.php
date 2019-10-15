<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class UsersChatRecords extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_chat_records';

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
    protected $fillable = ['source','user_id','receive_id','send_time','text_msg'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
