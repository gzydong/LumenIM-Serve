<?php

namespace App\Console\Commands;

use App\Models\Article\{Article, ArticleAnnex};
use App\Services\ArticleService;
use Illuminate\Console\Command;

set_time_limit(0);

class ForeverArticleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lumen-im:forever-article';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '永久删除笔记回收站中的笔记&回收站中的笔记附件';

    /**
     * @var ArticleService
     */
    protected $articleService;

    public function handle()
    {
        $this->articleService = new ArticleService();

        $this->deleteArticle();
        $this->deleteArticleAnnex();
    }

    /**
     * 永久删除笔记回收站中的笔记
     */
    public function deleteArticle()
    {
        $last_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        Article::select(['id', 'user_id'])->where('status', 2)->where('deleted_at', '<', $last_date)
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $this->articleService->foreverDelArticle($row['user_id'], $row['id']);
                }
            });
    }

    /**
     * 永久删除回收站中的笔记附件
     */
    public function deleteArticleAnnex()
    {
        $last_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        ArticleAnnex::select(['id', 'user_id'])->where('status', 2)->where('deleted_at', '<', $last_date)
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $this->articleService->foreverDelAnnex($row['user_id'], $row['id']);
                }
            });
    }
}
