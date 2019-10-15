<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class UsersChatRecordsMsg extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_chat_records_msg';

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
    protected $fillable = ['chat_record_id','msg_type','text_msg','img_msg','files_msg','created_time'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
