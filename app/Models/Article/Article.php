<?php
namespace App\Models\Article;

use App\Models\BaseModel;
class Article extends BaseModel
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'article';

    /**
     * 不能被批量赋值的属性
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * 关联笔记详细表(一对一关系)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function detail()
    {
        return $this->hasOne(ArticleDetail::class,'article_id','id');
    }

    /**
     * 关联笔记附件信息表(一对多关系)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function annexs()
    {
        return $this->hasMany(ArticleAnnex::class,'article_id','id');
    }
}
