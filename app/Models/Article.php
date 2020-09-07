<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Article extends Model
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
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

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
