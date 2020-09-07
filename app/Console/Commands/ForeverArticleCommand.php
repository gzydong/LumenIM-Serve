<?php

namespace App\Console\Commands;

use App\Logic\ArticleLogic;
use App\Models\Article;
use App\Models\ArticleAnnex;
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
     * @var ArticleLogic
     */
    protected $articleLogic;

    public function handle()
    {
        $this->articleLogic = new ArticleLogic();

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
                    $this->articleLogic->foreverDelArticle($row['user_id'], $row['id']);
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
                    $this->articleLogic->foreverDelAnnex($row['user_id'], $row['id']);
                }
            });
    }
}
