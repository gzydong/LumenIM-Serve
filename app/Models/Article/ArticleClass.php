<?php

namespace App\Models\Article;

use App\Models\BaseModel;
class ArticleClass extends BaseModel
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'article_class';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];
}
