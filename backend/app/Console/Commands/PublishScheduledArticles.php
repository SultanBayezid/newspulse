<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish';

    protected $description = 'Publish scheduled articles whose publish time has arrived';

    public function handle(ActivityLogger $logger): int
    {
        $articles = Article::query()
            ->where('status', ArticleStatus::Scheduled->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        foreach ($articles as $article) {
            $article->forceFill([
                'status' => ArticleStatus::Published,
            ])->save();

            $logger->log('article.published', 'Auto-published "' . $article->title . '" (scheduled)', $article);

            $this->info("Published: {$article->title}");
        }

        if ($articles->isEmpty()) {
            $this->info('No scheduled articles due for publishing.');
        }

        return self::SUCCESS;
    }
}
