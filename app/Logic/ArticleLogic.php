<?php
namespace App\Logic;

use App\Models\Article;
use App\Models\ArticleClass;
use App\Models\ArticleDetail;
use Illuminate\Support\Facades\DB;

/**
 * 文章处理逻辑层
 * @package App\Logic
 */
class ArticleLogic extends Logic
{
    /**
     * 获取用户文章分类列表
     *
     * @param int $uid 用户ID
     * @return array
     */
    public function getUserArticleClass(int $uid){
        $items = [
            ['id'=>0,'class_name'=>'默认文集']
        ];

        $res = ArticleClass::where('user_id',$uid)->orderBy('sort','asc')->get(['id','class_name'])->toArray();
        if($res){
            $items = array_merge($items,$res);
        }

        foreach ($items as &$item){
            $item['count'] = Article::where('user_id',$uid)->where('article_class_id',$item['id'])->count();
        }

        return $items;
    }

    /**
     * 获取用户文章列表
     *
     * @param int $user_id 用户ID
     * @param int $page 分页
     * @param int $page_size 分页大小
     * @param array $params 查询参数
     * @return array
     */
    public function getUserArticleList(int $user_id,int $page,int $page_size,$params = []){
        $countSqlObj = Article::select();
        $rowsSqlObj = Article::select(['article.id','article.article_class_id','article.title','article.abstract','article.updated_at','article_class.class_name'])
            ->leftJoin('article_class','article_class.id','=','article.article_class_id');

        $countSqlObj->where('article.user_id',$user_id);
        $rowsSqlObj->where('article.user_id',$user_id);

        if(isset($params['class_id'])){
            $countSqlObj->where('article.article_class_id',$params['class_id']);
            $rowsSqlObj->where('article.article_class_id',$params['class_id']);
        }

        if(isset($params['keyword'])){
            $countSqlObj->where('article.title','like',"%{$params['keyword']}%");
            $rowsSqlObj->where('article.title','like',"%{$params['keyword']}%");
        }

        $count = $countSqlObj->count();
        $rows = [];
        if ($count > 0) {
            $rows = $rowsSqlObj->orderBy('id', 'desc')->forPage($page, $page_size)->get()->toArray();
        }

        unset($countSqlObj);
        unset($rowsSqlObj);
        return $this->packData($rows, $count, $page, $page_size);
    }

    /**
     * 获取文章详情
     *
     * @param int $article_id 文章ID
     * @param int $uid 用户ID
     * @return array
     */
    public function getArticleDetail(int $article_id,$uid = 0){
        $info = Article::where('id',$article_id)->where('user_id',$uid)->first(['id','article_class_id','title','abstract','updated_at']);
        if(!$info) return [];

        $detail = $info->detail;
        if(!$detail)  return [];

        $data = [
            'id'=>$article_id,
            'title'=>$info->title,
            'abstract'=>$info->abstract,
            'updated_at'=>$info->updated_at,
            'class_id'=>$info->article_class_id,
            'md_content'=>htmlspecialchars_decode($detail->md_content),
            'content'=>htmlspecialchars_decode($detail->content)
        ];

        unset($info);
        unset($detail);
        return $data;
    }

    /**
     * 编辑文集分类
     *
     * @param int $uid 用户ID
     * @param int $class_id 分类ID
     * @param string $class_name 分类名
     * @return bool|int
     */
    public function editArticleClass(int $uid,int $class_id,string $class_name){
        if($class_id){
            if(!ArticleClass::where('id',$class_id)->where('user_id',$uid)->update(['class_name'=>$class_name])){
                return false;
            }
            return $class_id;
        }

        $sort = ArticleClass::where('user_id',$uid)->max('sort');

        $insRes = ArticleClass::create(['user_id'=>$uid,'class_name'=>$class_name,'sort'=>$sort + 1,'created_at'=>time()]);
        if (!$insRes) return false;

        return $insRes->id;
    }

    /**
     * 删除文章分类
     *
     * @param int $uid 用户ID
     * @param int $class_id 分类ID
     * @return bool
     */
    public function delArticleClass(int $uid,int $class_id){
        if(!ArticleClass::where('id',$class_id)->where('user_id',$uid)->exists()){
            return false;
        }

        DB::beginTransaction();
        try{
            $count = Article::where('user_id',$uid)->where('article_class_id',$class_id)->count();
            if($count > 0){
                throw new \Exception('该分类下存在文章...');
            }

            ArticleClass::where('id',$class_id)->where('user_id',$uid)->delete();
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return false;
        }

        return true;
    }

    /**
     * 编辑文章信息
     *
     * @param int $user_id 用户ID
     * @param int $article_id 文章ID
     * @param array $data 文章数据
     * @return bool
     */
    public function editArticle(int $user_id,int $article_id,$data = []){
        if($article_id){
            if(!$info = Article::where('id',$article_id)->where('user_id',$user_id)->first()){
                return false;
            }

            DB::beginTransaction();
            try{
                Article::where('id',$article_id)->where('user_id',$user_id)->update([
                    'article_class_id'=>$data['class_id'],
                    'title'=>$data['title'],
                    'abstract'=>$data['abstract'],
                    'updated_at'=>date('Y-m-d H:i:s')
                ]);

                ArticleDetail::where('article_id',$article_id)->update([
                    'md_content'=>$data['md_content'],
                    'content'=>$data['content']
                ]);

                DB::commit();
                return $article_id;
            }catch (\Exception $e){
                DB::rollBack();
            }

            return false;
        }

        DB::beginTransaction();
        try{
            $res = Article::create([
                'user_id'=>$user_id,
                'article_class_id'=>$data['class_id'],
                'title'=>$data['title'],
                'abstract'=>$data['abstract'],
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s')
            ]);

            ArticleDetail::create([
               'article_id'=>$res->id,
               'md_content'=>$data['md_content'],
               'content'=>$data['content']
            ]);

            DB::commit();
            return $res->id;
        }catch (\Exception $e){
            DB::rollBack();
        }

        return false;
    }

    /**
     * 文集分类置顶
     *
     * @param int $user_id 用户ID
     * @param int $class_id 文集分类ID
     * @return bool
     */
    public function articleClassSort(int $user_id,int $class_id){
        if(!Article::where('id',$class_id)->where('user_id',$user_id)->exists()){
            return false;
        }

        $array = [];
        $items = ArticleClass::where('user_id',$user_id)->orderBy('sort','asc')->get(['id','class_name']);
        foreach ($items as $item){
            if($item->id == $class_id){
                array_unshift($array,$item);
            }else{
                $array[] = $item;
            }
        }

        unset($items);

        DB::beginTransaction();
        try{
            foreach ($array as $sort=>$val){
                ArticleClass::where('id',$val->id)->update(['sort'=>$sort+1]);
            }

            DB::commit();
            return true;
        }catch (\Exception $e){
            DB::rollBack();
        }

        return false;
    }

    /**
     * 文集合并
     *
     * @param int $user_id 用户ID
     * @param int $class_id 文集ID
     * @param int $toid 合并文集ID
     * @return bool
     */
    public function mergeArticleClass(int $user_id,int $class_id,int $toid){
        $count = ArticleClass::whereIn('id',[$class_id,$toid])->where('user_id',$user_id)->count();
        if($count < 2){
            return false;
        }

        return Article::where('article_class_id',$class_id)->where('user_id',$user_id)->update([
            'article_class_id'=>$toid
        ]);
    }
}