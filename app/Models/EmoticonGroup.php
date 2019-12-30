<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmoticonGroup extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'emoticon_group';

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
    protected $fillable = ['name','url','created_at'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
