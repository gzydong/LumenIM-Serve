<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersEmoticon extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'users_emoticon';

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
    protected $fillable = ['user_id','emoticon_ids'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     *
     * @param  string  $value
     * @return string
     */
    public function getEmoticonIdsAttribute($value)
    {
        return explode(',',$value);
    }
}

