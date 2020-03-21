<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\ArticleDetail;

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
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['user_id','article_class_id','title','image','abstract','created_at','updated_at'];

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 获取获取商品轮播图
     */
    public function detail()
    {
        return $this->hasOne(ArticleDetail::class,'article_id','id');
    }
}
