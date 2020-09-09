<?php

namespace App\Http\Controllers\Api;

use App\Helpers\RedisLock;
use Illuminate\Http\Request;
use App\Logic\ArticleLogic;
use Illuminate\Support\Facades\Storage;

class ArticleController extends CController
{
    public $request;
    public $articleLogic;

    public function __construct(Request $request, ArticleLogic $articleLogic)
    {
        $this->request = $request;
        $this->articleLogic = $articleLogic;
    }

    /**
     * 获取笔记分类列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticleClass()
    {
        return $this->ajaxSuccess('success', [
            'rows' => $this->articleLogic->getUserArticleClass($this->uid()),
        ]);
    }

    /**
     * 获取笔记标签列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticleTags()
    {
        $user_id = $this->uid();
        return $this->ajaxSuccess('success', [
            'tags' => $this->articleLogic->getUserArticleTags($user_id)
        ]);
    }

    /**
     * 获取笔记列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticleList()
    {
        //搜索关键词
        $keyword = $this->request->get('keyword', '');
        //查询类型 $findType 1:获取近期日记  2:获取星标日记  3:获取指定分类文章  4:获取指定标签文章 5:获取已删除文章 6:关键词搜索
        $findType = $this->request->get('find_type', 0);
        //分类ID
        $cid = $this->request->get('cid', -1);
        $page = $this->request->get('page', 1);

        if (!in_array($findType, [1, 2, 3, 4, 5, 6]) || !isInt($page)) {
            return $this->ajaxParamError();
        }

        $params = [];
        $params['find_type'] = $findType;
        if (in_array($findType, [3, 4])) {
            $params['class_id'] = $cid;
        }

        if (!empty($keyword)) {
            $params['keyword'] = addslashes($keyword);
        }

        $data = $this->articleLogic->getUserArticleList($this->uid(), $page, 2000, $params);
        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 编辑用户笔记
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editArticle()
    {
        $article_id = $this->request->post('article_id', 0);
        $class_id = $this->request->post('class_id', 0);
        $md_content = $this->request->post('md_content', '');
        $content = $this->request->post('content', '');
        $title = $this->request->post('title', '');

        if (!isInt($article_id, true) || !isInt($class_id, true) || empty($title) || empty($md_content) || empty($content)) {
            return $this->ajaxParamError();
        }

        $id = $this->articleLogic->editArticle($this->uid(), $article_id, [
            'title' => $title,
            'abstract' => mb_substr(strip_tags($content), 0, 200),
            'class_id' => $class_id,
            'image' => getTtmlImgs($content),
            'md_content' => htmlspecialchars($md_content),
            'content' => htmlspecialchars($content)
        ]);

        return $id ? $this->ajaxSuccess('笔记编辑成功...', [
            'aid' => $id
        ]) : $this->ajaxReturn(303, '笔记编辑失败...', ['id' => null]);
    }

    /**
     * 获取笔记详情
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticleDetail()
    {
        $article_id = $this->request->get('article_id', 0);
        if (!isInt($article_id)) {
            return $this->ajaxParamError();
        }

        $data = $this->articleLogic->getArticleDetail($article_id, $this->uid());

        return empty($data) ? $this->ajaxReturn(303, '文章信息不存在') :
            $this->ajaxSuccess('success', $data);
    }

    /**
     * 添加或编辑笔记分类
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editArticleClass()
    {
        $class_id = $this->request->post('class_id', 0);
        $class_name = $this->request->post('class_name', '');

        if (!isInt($class_id, true) || empty($class_name)) {
            return $this->ajaxParamError();
        }

        $id = $this->articleLogic->editArticleClass($this->uid(), $class_id, $class_name);

        return $id ? $this->ajaxSuccess('success', ['id' => $id]) : $this->ajaxError('编辑失败...');
    }

    /**
     * 添加或编辑笔记标签
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editArticleTags()
    {
        $tag_id = $this->request->post('tag_id', 0);
        $tag_name = $this->request->post('tag_name', '');

        if (!isInt($tag_id, true) || empty($tag_name)) {
            return $this->ajaxParamError();
        }

        $id = $this->articleLogic->editArticleTag($this->uid(), $tag_id, $tag_name);
        return $id ? $this->ajaxSuccess('success', ['id' => $id]) : $this->ajaxError('编辑失败...');
    }

    /**
     * 删除笔记分类
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delArticleClass()
    {
        $class_id = $this->request->post('class_id', 0);
        if (!isInt($class_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->delArticleClass($this->uid(), $class_id);
        return $isTrue ? $this->ajaxSuccess('删除完成...') : $this->ajaxError('删除失败...');
    }

    /**
     * 删除笔记标签
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delArticleTags()
    {
        $tag_id = $this->request->post('tag_id', 0);
        if (!isInt($tag_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->delArticleTags($this->uid(), $tag_id);

        return $isTrue ?
            $this->ajaxSuccess('删除标签完成...') :
            $this->ajaxError('删除标签失败...');
    }

    /**
     * 笔记分类列表排序接口
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function articleClassSort()
    {
        $class_id = $this->request->post('class_id', 0);
        $sort_type = $this->request->post('sort_type', 0);
        $user_id = $this->uid();
        if (!isInt($class_id) || !in_array($sort_type, [1, 2])) {
            return $this->ajaxParamError();
        }

        $isTrue = false;
        $lockKey = "article_class_sort:{$user_id}_{$class_id}";
        if (RedisLock::lock($lockKey, 0, 5)) {
            $isTrue = $this->articleLogic->articleClassSort($user_id, $class_id, $sort_type);

            RedisLock::release($lockKey, 0);
        }

        return $isTrue ? $this->ajaxSuccess('排序完成...') : $this->ajaxError('排序失败...');
    }

    /**
     * 笔记分类合并接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mergeArticleClass()
    {
        $class_id = $this->request->post('class_id', 0);
        $toid = $this->request->post('toid', 0);
        if (!isInt($class_id) || !isInt($toid)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->mergeArticleClass($this->uid(), $class_id, $toid);
        return $isTrue ? $this->ajaxSuccess('合并完成...') : $this->ajaxError('合并失败...');
    }

    /**
     * 移动笔记至指定分类
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveArticle()
    {
        $article_id = $this->request->post('article_id', 0);
        $class_id = $this->request->post('class_id', 0);
        if (!isInt($article_id) || !isInt($class_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->moveArticle($this->uid(), $article_id, $class_id);
        return $isTrue ? $this->ajaxSuccess('操作完成...') : $this->ajaxError('操作失败...');
    }

    /**
     * 笔记标记星号接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAsteriskArticle()
    {
        $article_id = $this->request->post('article_id', 0);
        $type = $this->request->post('type', 0);
        if (!isInt($article_id) || !in_array($type, [1, 2])) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->setAsteriskArticle($this->uid(), $article_id, $type);
        return $isTrue ? $this->ajaxSuccess('success') : $this->ajaxError('fail');
    }

    /**
     * 笔记图片上传接口
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadArticleImage()
    {
        $file = $this->request->file('image');

        if (!$file->isValid()) {
            return $this->ajaxParamError('图片上传失败，请稍后再试...');
        }

        $ext = $file->extension();
        //图片格式验证
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->ajaxParamError('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        $imgInfo = getimagesize($file->getRealPath());
        $filename = getSaveImgName($ext, $imgInfo[0], $imgInfo[1]);

        //保存图片
        if (!$save_path = Storage::disk('uploads')->putFileAs('media/images/notes/' . date('Ymd'), $file, $filename)) {
            return $this->ajaxError('图片上传失败，请稍后再试...');
        }

        return $this->ajaxSuccess('success', ['save_path' => getFileUrl($save_path)]);
    }

    /**
     * 上传笔记附件
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadArticleAnnex()
    {
        $file = $this->request->file('annex');
        $article_id = $this->request->post('article_id', 0);
        if (!$file->isValid() || !isInt($article_id)) {
            return $this->ajaxParamError('附件上传失败，请稍后再试...');
        }

        $user_id = $this->uid();
        $ext = $file->getClientOriginalExtension();

        $annex = [
            'file_suffix' => $ext,
            'file_size' => $file->getSize(),
            'save_dir' => '',
            'original_name' => $file->getClientOriginalName()
        ];

        if (!$save_path = Storage::disk('uploads')->putFileAs('files/notes/' . date('Ymd'), $file, "[{$ext}]" . uniqid() . str_random(16) . '.' . 'tmp')) {
            return $this->ajaxError('附件上传失败，请稍后再试...');
        }

        $annex['save_dir'] = $save_path;
        $insId = $this->articleLogic->insertArticleAnnex($user_id, $article_id, $annex);
        if (!$insId) {
            return $this->ajaxError('附件上传失败，请稍后再试...');
        }

        $annex['id'] = $insId;
        return $this->ajaxSuccess('success', $annex);
    }

    /**
     * 删除笔记附件
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteArticleAnnex()
    {
        $annex_id = $this->request->post('annex_id', 0);
        if (!isInt($annex_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->updateArticleAnnexStatus($this->uid(), $annex_id, 2);
        return $isTrue ? $this->ajaxSuccess('附件删除成功...') : $this->ajaxError('附件删除失败...');
    }

    /**
     * 永久删除笔记附件(从已删除附件中永久删除)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function foreverDelAnnex()
    {
        $annex_id = $this->request->post('annex_id', 0);
        if (!isInt($annex_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->foreverDelAnnex($this->uid(), $annex_id);
        return $isTrue ? $this->ajaxSuccess('附件删除成功...') : $this->ajaxError('附件删除失败...');
    }

    /**
     * 恢复笔记附件
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverArticleAnnex()
    {
        $annex_id = $this->request->post('annex_id', 0);
        if (!isInt($annex_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->updateArticleAnnexStatus($this->uid(), $annex_id, 1);
        return $isTrue ? $this->ajaxSuccess('附件恢复成功...') : $this->ajaxError('附件恢复失败...');
    }

    /**
     * 获取附件回收站列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverAnnexList()
    {
        $rows = $this->articleLogic->recoverAnnexList($this->uid());
        if ($rows) {
            $getDay = function ($delete_at) {
                $last_time = strtotime('+30 days', strtotime($delete_at));

                return (time() > $last_time) ? 0 : diffDate(date('Y-m-d', $last_time), date('Y-m-d'));
            };

            array_walk($rows, function (&$item) use ($getDay) {
                $item['day'] = $getDay($item['deleted_at']);
                $item['visible'] = false;
            });
        }

        return $this->ajaxSuccess('success', ['rows' => $rows]);
    }

    /**
     * 删除笔记
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteArticle()
    {
        $article_id = $this->request->post('article_id', 0);
        if (!isInt($article_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->updateArticleStatus($this->uid(), $article_id, 2);
        return $isTrue ? $this->ajaxSuccess('笔记删除成功...') : $this->ajaxError('笔记删除失败...');
    }

    /**
     * 恢复笔记
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recoverArticle()
    {
        $article_id = $this->request->post('article_id', 0);
        if (!isInt($article_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->updateArticleStatus($this->uid(), $article_id, 1);
        return $isTrue ? $this->ajaxSuccess('笔记恢复成功...') : $this->ajaxError('笔记恢复失败...');
    }

    /**
     * 永久删除笔记文章
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function foreverDelArticle()
    {
        $article_id = $this->request->post('article_id', 0);
        if (!isInt($article_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->foreverDelArticle($this->uid(), $article_id);
        return $isTrue ? $this->ajaxSuccess('笔记删除成功...') : $this->ajaxError('笔记删除失败...');
    }

    /**
     * 更新笔记关联标签ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateArticleTag()
    {
        $article_id = $this->request->post('article_id', 0);
        $tags = $this->request->post('tags', []);
        if (!isInt($article_id) || !checkIds($tags)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->updateArticleTag($this->uid(), $article_id, $tags);
        return $isTrue ? $this->ajaxSuccess('success') : $this->ajaxError('编辑失败...');
    }
}
