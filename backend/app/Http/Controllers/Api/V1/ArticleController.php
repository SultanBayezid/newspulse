<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleDetailResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * Published articles with filters, search and sorting.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', $this->settings->int('articles_per_page', 12));
        $perPage = max(1, min(50, $perPage));

        $query = Article::published()
            ->with(['cover', 'category', 'author.avatar'])
            ->withCount('approvedComments');

        $this->applyFilters($query, $request);

        $sort = $request->query('sort', 'latest');

        $query->when($sort === 'popular', fn (Builder $q) => $q->orderByDesc('views_count'))
            ->when($sort === 'oldest', fn (Builder $q) => $q->orderBy('published_at'))
            ->when($sort === 'latest' || $sort === null, fn (Builder $q) => $q->orderByDesc('published_at'));

        return ArticleResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    /**
     * Most read articles of the last 24 hours.
     */
    public function trending(Request $request): AnonymousResourceCollection
    {
        $limit = max(1, min(20, (int) $request->query('limit', 6)));

        $articles = Article::published()
            ->with(['cover', 'category', 'author.avatar'])
            ->selectRaw(
                'articles.*, (select coalesce(sum(views.count), 0) from article_views as views '
                . 'where views.article_id = articles.id and views.viewed_on >= ?) as views_recent',
                [now()->subDay()->toDateString()]
            )
            ->orderByDesc('views_recent')
            ->orderByDesc('views_count')
            ->take($limit)
            ->get();

        return ArticleResource::collection($articles);
    }

    /**
     * Breaking news from the last 24 hours.
     */
    public function breaking(): AnonymousResourceCollection
    {
        return ArticleResource::collection(
            Article::published()
                ->with(['cover', 'category', 'author.avatar'])
                ->breaking()
                ->orderByDesc('published_at')
                ->take(10)
                ->get()
        );
    }

    /**
     * A single published article (increments its view counter).
     */
    public function show(string $slug): \Illuminate\Http\JsonResponse
    {
        $article = Article::published()
            ->with(['cover', 'category', 'author.avatar', 'tags', 'gallery'])
            ->withCount('approvedComments')
            ->where('slug', $slug)
            ->firstOrFail();

        $this->recordView($article);

        $related = Article::published()
            ->with(['cover', 'category', 'author.avatar'])
            ->withCount('approvedComments')
            ->where('id', '!=', $article->id)
            ->when(
                $article->category_id,
                fn (Builder $q) => $q->where('category_id', $article->category_id),
                fn (Builder $q) => $q
            )
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        if ($related->count() < 4) {
            $extra = Article::published()
                ->with(['cover', 'category', 'author.avatar'])
                ->withCount('approvedComments')
                ->where('id', '!=', $article->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderByDesc('published_at')
                ->take(4 - $related->count())
                ->get();

            $related = $related->concat($extra);
        }

        return response()->json([
            'data' => new ArticleDetailResource($article),
            'related' => ArticleResource::collection($related),
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when(
                $request->filled('category'),
                fn (Builder $q) => $q->whereHas(
                    'category',
                    fn (Builder $c) => $c->where('slug', $request->query('category'))
                )
            )
            ->when(
                $request->filled('tag'),
                fn (Builder $q) => $q->whereHas(
                    'tags',
                    fn (Builder $t) => $t->where('slug', $request->query('tag'))
                )
            )
            ->when(
                $request->filled('author'),
                fn (Builder $q) => $q->where('user_id', (int) $request->query('author'))
            )
            ->when(
                $request->filled('language'),
                fn (Builder $q) => $q->ofLanguage((string) $request->query('language'))
            )
            ->when(
                $request->boolean('featured'),
                fn (Builder $q) => $q->featured()
            );

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $request->query('q')).'%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('body', 'like', $term);
            });
        }
    }

    private function recordView(Article $article): void
    {
        DB::transaction(function () use ($article) {
            $today = now()->toDateString();

            $updated = DB::table('article_views')
                ->where('article_id', $article->id)
                ->where('viewed_on', $today)
                ->update(['count' => DB::raw('article_views.count + 1')]);

            if ($updated === 0) {
                try {
                    DB::table('article_views')->insert([
                        'article_id' => $article->id,
                        'viewed_on' => $today,
                        'count' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException) {
                    // Concurrent insert won the race - increment instead.
                    DB::table('article_views')
                        ->where('article_id', $article->id)
                        ->where('viewed_on', $today)
                        ->update(['count' => DB::raw('article_views.count + 1')]);
                }
            }

            Article::withoutTimestamps(fn () => $article->increment('views_count'));
        });
    }
}
