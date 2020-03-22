<?php

namespace App\Http\Controllers\Api;

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
     * 获取文章分类列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticleClass()
    {
        $items = $this->articleLogic->getUserArticleClass($this->uid());
        return $this->ajaxSuccess('success', $items);
    }

    /**
     * 获取文章列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticleList()
    {
        //搜索关键词
        $keyword = $this->request->get('keyword', '');
        //查询类型 $findType 1:获取近期日记  2:获取星标日记  3:获取指定分类文章  4:获取指定标签文章 5:获取已删除文章
        $findType = $this->request->get('find_type', 0);
        //分类ID
        $cid = $this->request->get('cid', -1);
        $page = $this->request->get('page', 1);

        if(!in_array($findType,[1,2,3,4]) || !isInt($page)){
            return $this->ajaxParamError();
        }

        $params = [];
        $params['find_type'] = $findType;
        if (in_array($findType,[3,4])) {
            $params['class_id'] = $cid;
        }

        if (!empty($keyword)) {
            $params['keyword'] = addslashes($keyword);
        }

        $data = $this->articleLogic->getUserArticleList($this->uid(), $page, 1000, $params);
        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 编辑文章
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
            'md_content' => htmlspecialchars($md_content),
            'content' => htmlspecialchars($content)
        ]);

        if (!$id) {
            return $this->ajaxReturn(303, '文章编辑失败...', ['id' => $id]);
        }

        return $this->ajaxSuccess('文章编辑成功...', ['aid' => $id]);
    }

    /**
     * 获取文章详情
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
        if (empty($data)) {
            return $this->ajaxReturn(303, '文章信息不存在');
        }

        return $this->ajaxSuccess('success', $data);
    }

    /**
     * 编辑文章分类
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
        if (!$id) {
            return $this->ajaxError('编辑失败...');
        }

        return $this->ajaxSuccess('success', ['id' => $id]);
    }

    /**
     * 删除文章分类
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
     * 文章列表排序接口
     */
    public function articleClassSort()
    {
        $class_id = $this->request->post('class_id', 0);
        if (!isInt($class_id)) {
            return $this->ajaxParamError();
        }

        $isTrue = $this->articleLogic->articleClassSort($this->uid(), $class_id);
        return $isTrue ? $this->ajaxSuccess('置顶完成...') : $this->ajaxError('置顶失败...');
    }


    /**
     * 文集合并接口
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
     * 上传笔记图片
     */
    public function uploadArticleImage(){
        $file = $this->request->file('image');

        if(!$file->isValid()){
            return $this->ajaxParamError('图片上传失败，请稍后再试...');
        }

        $ext = $file->extension();
        //图片格式验证
        if (!in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            return $this->ajaxParamError('图片格式错误，目前仅支持jpg、png、jpeg、gif和webp');
        }

        $imgInfo = getimagesize($file->getRealPath());
        $filename = getSaveImgName($ext,$imgInfo[0],$imgInfo[1]);

        //保存图片
        if (!$save_path = Storage::disk('uploads')->putFileAs('images/' . date('Ymd'), $file, $filename)) {
            return $this->ajaxError('图片上传失败，请稍后再试...');
        }

        return $this->ajaxSuccess('success',['save_path'=>getFileUrl($save_path)]);
    }
}
