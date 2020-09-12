<?php

namespace App\Models\Article;

use Illuminate\Database\Eloquent\Model;

class ArticleDetail extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'article_detail';

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
}
