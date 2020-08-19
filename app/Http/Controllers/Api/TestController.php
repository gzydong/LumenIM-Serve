<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use App\Models\ArticleClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


/**
 * 测试控制器
 *
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends CController
{
    public function test()
    {

        $subJoin = Article::select('class_id',DB::raw('count(class_id) as count'))->where('user_id',2054)->groupBy('class_id');
        $aa = ArticleClass::joinSub($subJoin, 'latest_posts', function($join) {
            $join->on('article_class.id', '=', DB::raw('latest_posts.class_id'));
        })->where('article_class.user_id', 2054)->orderBy('article_class.sort', 'asc')
            ->get(['article_class.id', 'article_class.class_name', 'article_class.is_default',DB::raw('latest_posts.count')])->toArray();




        dd($aa);
    }

    public function index(Request $request)
    {

    }
}


