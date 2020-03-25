<?php

namespace App\Logic;

use App\Models\Article;
use App\Models\ArticleClass;
use App\Models\ArticleDetail;
use App\Models\ArticleTags;
use App\Models\ArticleTagsRelation;
use Illuminate\Support\Facades\DB;

/**
 * 文章处理逻辑层
 * @package App\Logic
 */
class ArticleLogic extends Logic
{

    /**
     * 检测并创建用户的默认分类
     *
     * @param int $uid
     */
    public function checkDefaultClass(int $uid)
    {
        if (!ArticleClass::where('user_id', $uid)->where('is_default', 1)->exists()) {
            ArticleClass::create([
                'user_id' => $uid,
                'class_name' => '我的笔记',
                'is_default' => 1,
                'sort' => 1,
                'created_at' => time()
            ]);
        }
    }

    /**
     * 获取用户文章分类列表
     *
     * @param int $uid 用户ID
     * @return array
     */
    public function getUserArticleClass(int $uid)
    {
        $items = ArticleClass::where('user_id', $uid)->orderBy('sort', 'asc')->get(['id', 'class_name', 'is_default'])->toArray();
        foreach ($items as &$item) {
            $item['count'] = Article::where('user_id', $uid)->where('class_id', $item['id'])->count();
        }

        return $items;
    }

    /**
     * 获取用户文章标签列表
     *
     * @param int $uid 用户ID
     * @return mixed
     */
    public function getUserArticleTags(int $uid)
    {
        $items = ArticleTags::where('user_id', $uid)->orderBy('id', 'desc')->get(['id', 'tag_name'])->toArray();
        if ($items) {
            foreach ($items as &$item) {
                $item['count'] = ArticleTagsRelation::where('user_id', $uid)->where('tag_id', $item['id'])->count();
            }
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

    public function getUserArticleList(int $user_id, int $page, int $page_size, $params = [])
    {
        $filed = ['article.id', 'article.class_id', 'article.title', 'article.image', 'article.abstract', 'article.updated_at', 'article_class.class_name'];

        $countSqlObj = Article::select();
        $rowsSqlObj = Article::select($filed)
            ->leftJoin('article_class', 'article_class.id', '=', 'article.class_id');

        $countSqlObj->where('article.user_id', $user_id);
        $rowsSqlObj->where('article.user_id', $user_id);

        if ($params['find_type'] == 3) {
            $countSqlObj->where('article.class_id', $params['class_id']);
            $rowsSqlObj->where('article.class_id', $params['class_id']);
        } else if ($params['find_type'] == 4) {
            $func = function ($join) use ($params) {
                $join->on('article.id', '=', 'article_tags_relation.article_id')->where('article_tags_relation.tag_id', '=', $params['class_id']);
            };

            $countSqlObj->join('article_tags_relation', $func);
            $rowsSqlObj->join('article_tags_relation', $func);
        } else if ($params['find_type'] == 2) {
            $countSqlObj->where('article.is_asterisk', 1);
            $rowsSqlObj->where('article.is_asterisk', 1);
        }

        if (isset($params['keyword'])) {
            $countSqlObj->where('article.title', 'like', "%{$params['keyword']}%");
            $rowsSqlObj->where('article.title', 'like', "%{$params['keyword']}%");
        }

        $count = $countSqlObj->count();
        $rows = [];
        if ($count > 0) {
            if ($params['find_type'] == 1) {
                $rowsSqlObj->orderBy('updated_at', 'desc');
                $page_size = 20;
            } else {
                $rowsSqlObj->orderBy('id', 'desc');
            }

            $rows = $rowsSqlObj->forPage($page, $page_size)->get()->toArray();
        }

        return $this->packData($rows, $count, $page, $page_size);
    }

    /**
     * 获取文章详情
     *
     * @param int $article_id 文章ID
     * @param int $uid 用户ID
     * @return array
     */
    public function getArticleDetail(int $article_id, $uid = 0)
    {
        $info = Article::where('id', $article_id)->where('user_id', $uid)->first(['id', 'class_id', 'title', 'abstract', 'is_asterisk', 'updated_at']);
        if (!$info) return [];

        $detail = $info->detail;
        if (!$detail) return [];

        $data = [
            'id' => $article_id,
            'title' => $info->title,
            'abstract' => $info->abstract,
            'updated_at' => $info->updated_at,
            'class_id' => $info->class_id,
            'md_content' => htmlspecialchars_decode($detail->md_content),
            'content' => htmlspecialchars_decode($detail->content),
            'is_asterisk' => $info->is_asterisk,
            'tags' => ArticleTagsRelation::leftJoin('article_tags', 'article_tags.id', '=', 'article_tags_relation.tag_id')->where('article_tags_relation.article_id', $article_id)->get([
                'article_tags.id', 'article_tags.tag_name'
            ])
        ];

        unset($info);

        return $data;
    }

    /**
     * 编辑笔记分类
     *
     * @param int $uid 用户ID
     * @param int $class_id 分类ID
     * @param string $class_name 分类名
     * @return bool|int
     */
    public function editArticleClass(int $uid, int $class_id, string $class_name)
    {
        if ($class_id) {
            if (!ArticleClass::where('id', $class_id)->where('user_id', $uid)->where('is_default', 0)->update(['class_name' => $class_name])) {
                return false;
            }
            return $class_id;
        }

        $arr = [];
        $items = ArticleClass::where('user_id', $uid)->get(['id', 'sort']);
        foreach ($items as $key => $item) {
            $arr[] = ['id' => $item->id, 'sort' => $key + 2];
        }

        unset($items);

        DB::beginTransaction();
        try {
            foreach ($arr as $val) {
                ArticleClass::where('id', $val['id'])->update(['sort' => $val['sort']]);
            }

            $insRes = ArticleClass::create(['user_id' => $uid, 'class_name' => $class_name, 'sort' => 1, 'created_at' => time()]);
            if (!$insRes) {
                throw new \Exception('添加错误..,.');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        return $insRes->id;
    }

    /**
     * 编辑笔记标签
     *
     * @param int $uid 用户ID
     * @param int $tag_id 标签ID
     * @param string $tag_name 标签名
     * @return bool|int
     */
    public function editArticleTag(int $uid, int $tag_id, string $tag_name)
    {
        if ($tag_id) {
            if (!ArticleTags::where('id', $tag_id)->where('user_id', $uid)->update(['tag_name' => $tag_name])) {
                return false;
            }

            return $tag_id;
        }

        $insRes = ArticleTags::create(['user_id' => $uid, 'tag_name' => $tag_name, 'sort' => 1, 'created_at' => time()]);
        if (!$insRes) {
            return false;
        }

        return $insRes->id;
    }

    /**
     * 删除笔记分类
     *
     * @param int $uid 用户ID
     * @param int $class_id 分类ID
     * @return bool
     */
    public function delArticleClass(int $uid, int $class_id)
    {
        if (!ArticleClass::where('id', $class_id)->where('user_id', $uid)->exists()) {
            return false;
        }

        $count = Article::where('user_id', $uid)->where('class_id', $class_id)->count();
        if ($count > 0) {
            return false;
        }
        return (bool)ArticleClass::where('id', $class_id)->where('user_id', $uid)->where('is_default', 0)->delete();
    }

    /**
     * 删除笔记标签
     *
     * @param int $uid 用户ID
     * @param int $tag_id 标签ID
     * @return bool
     */
    public function delArticleTags(int $uid, int $tag_id)
    {
        if (!ArticleTags::where('id', $tag_id)->where('user_id', $uid)->exists()) {
            return false;
        }

        $count = Article::where('user_id', $uid)->where('tag_id', $tag_id)->count();
        if ($count > 0) {
            return false;
        }
        return (bool)ArticleTags::where('id', $tag_id)->where('user_id', $uid)->delete();
    }

    /**
     * 编辑文章信息
     *
     * @param int $user_id 用户ID
     * @param int $article_id 文章ID
     * @param array $data 文章数据
     * @return bool
     */
    public function editArticle(int $user_id, int $article_id, $data = [])
    {
        if ($article_id) {
            if (!$info = Article::where('id', $article_id)->where('user_id', $user_id)->first()) {
                return false;
            }

            DB::beginTransaction();
            try {
                Article::where('id', $article_id)->where('user_id', $user_id)->update([
                    'class_id' => $data['class_id'],
                    'title' => $data['title'],
                    'abstract' => $data['abstract'],
                    'image' => $data['image'] ? $data['image'][0] : '',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                ArticleDetail::where('article_id', $article_id)->update([
                    'md_content' => $data['md_content'],
                    'content' => $data['content']
                ]);

                DB::commit();
                return $article_id;
            } catch (\Exception $e) {
                DB::rollBack();
            }

            return false;
        }

        DB::beginTransaction();
        try {
            $res = Article::create([
                'user_id' => $user_id,
                'class_id' => $data['class_id'],
                'title' => $data['title'],
                'abstract' => $data['abstract'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            ArticleDetail::create([
                'article_id' => $res->id,
                'md_content' => $data['md_content'],
                'content' => $data['content']
            ]);

            DB::commit();
            return $res->id;
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return false;
    }

    /**
     * 文集分类排序
     *
     * @param int $user_id 用户ID
     * @param int $class_id 文集分类ID
     * @param int $sort_type 排序方式
     * @return bool
     */
    public function articleClassSort(int $user_id, int $class_id, int $sort_type)
    {
        if (!$info = ArticleClass::select(['id', 'sort'])->where('id', $class_id)->where('user_id', $user_id)->first()) {
            return false;
        }

        //向下排序
        if ($sort_type == 1) {
            $maxSort = ArticleClass::where('user_id', $user_id)->max('sort');
            if ($maxSort == $info->sort) {
                return false;
            }

            DB::beginTransaction();
            try {
                ArticleClass::where('user_id', $user_id)->where('sort', $info->sort + 1)->update([
                    'sort' => $info->sort
                ]);

                ArticleClass::where('id', $class_id)->update([
                    'sort' => $info->sort + 1
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return false;
            }

            return true;
        } else if ($sort_type == 2) {//向上排序
            $minSort = ArticleClass::where('user_id', $user_id)->min('sort');
            if ($minSort == $info->sort) {
                return false;
            }

            DB::beginTransaction();
            try {
                ArticleClass::where('user_id', $user_id)->where('sort', $info->sort - 1)->update([
                    'sort' => $info->sort
                ]);

                ArticleClass::where('id', $class_id)->where('user_id', $user_id)->update([
                    'sort' => $info->sort - 1
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return false;
            }

            return true;
        }
    }

    /**
     * 文集合并
     *
     * @param int $user_id 用户ID
     * @param int $class_id 文集ID
     * @param int $toid 合并文集ID
     * @return bool
     */
    public function mergeArticleClass(int $user_id, int $class_id, int $toid)
    {
        $count = ArticleClass::whereIn('id', [$class_id, $toid])->where('user_id', $user_id)->count();
        if ($count < 2) {
            return false;
        }

        return Article::where('class_id', $class_id)->where('user_id', $user_id)->update([
            'class_id' => $toid
        ]);
    }

    /**
     * 笔记移动至指定分类
     *
     * @param int $user_id 用户ID
     * @param int $article_id 笔记ID
     * @param int $class_id 笔记分类ID
     * @return bool
     */
    public function moveArticle(int $user_id, int $article_id, int $class_id)
    {
        $info = Article::where('id', $article_id)->where('user_id', $user_id)->first(['id', 'class_id']);
        if (!$info || $info->class_id == $class_id) {
            return false;
        }

        $info->class_id = $class_id;
        $info->save();
        return true;
    }

    /**
     * 笔记标记星号
     *
     * @param int $user_id 用户ID
     * @param int $article_id 笔记ID
     * @param int $type 1:标记星号 2:取消星号标记
     * @return bool
     */
    public function setAsteriskArticle(int $user_id, int $article_id, int $type)
    {
        $info = Article::where('id', $article_id)->where('user_id', $user_id)->first(['id', 'is_asterisk']);
        if (!$info) {
            return false;
        }

        if (($type == 1 && $info->is_asterisk == 1) || ($type == 2 && $info->is_asterisk == 0)) {
            return true;
        }

        $info->is_asterisk = $type == 1 ? 1 : 0;
        $info->save();
        return true;
    }
}
