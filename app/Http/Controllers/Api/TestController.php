<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ArticleValidate;
use App\Models\Article\Article;
use Illuminate\Http\Request;

/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test(Request $request)
    {

        $this->validate($request, [
            'article_id' => 'required|Integer|min:0',
            'class_id' => 'required|Integer|min:0',
            'title' => 'required|max:255',
            'content' => 'required',
            'md_content' => 'required',
        ]);
    }
}
