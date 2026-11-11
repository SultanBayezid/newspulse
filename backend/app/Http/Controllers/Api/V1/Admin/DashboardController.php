<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\ArticleResource;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * Editorial dashboard statistics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $viewsByDay = DB::table('article_views')
            ->whereBetween('viewed_on', [now()->subDays(29)->toDateString(), now()->toDateString()])
            ->groupBy('viewed_on')
            ->selectRaw('viewed_on, sum(count) as total')
            ->pluck('total', 'viewed_on');

        $articlesByDay = Article::query()
            ->whereBetween('published_at', [now()->subDays(29)->startOfDay(), now()])
            ->whereNotNull('published_at')
            ->groupBy(DB::raw('date(published_at)'))
            ->selectRaw('date(published_at) as day, count(*) as total')
            ->pluck('total', 'day');

        $viewsChart = collect(range(29, 0))->map(function (int $daysAgo) use ($viewsByDay) {
            $day = now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $day,
                'count' => (int) ($viewsByDay[$day] ?? 0),
            ];
        })->values();

        $articlesChart = collect(range(29, 0))->map(function (int $daysAgo) use ($articlesByDay) {
            $day = now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $day,
                'count' => (int) ($articlesByDay[$day] ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => [
                'totals' => [
                    'articles' => Article::count(),
                    'published' => Article::where('status', 'published')->count(),
                    'drafts' => Article::where('status', 'draft')->count(),
                    'in_review' => Article::where('status', 'review')->count(),
                    'scheduled' => Article::where('status', 'scheduled')->count(),
                    'users' => \App\Models\User::count(),
                    'comments' => \App\Models\Comment::count(),
                    'pending_comments' => \App\Models\Comment::where('status', 'pending')->count(),
                    'subscribers' => \App\Models\NewsletterSubscriber::where('status', 'subscribed')->count(),
                    'media' => \App\Models\Media::count(),
                    'views_today' => (int) DB::table('article_views')->where('viewed_on', today()->toDateString())->sum('count'),
                    'views_7d' => (int) DB::table('article_views')->where('viewed_on', '>=', now()->subDays(6)->toDateString())->sum('count'),
                    'views_30d' => (int) DB::table('article_views')->where('viewed_on', '>=', now()->subDays(29)->toDateString())->sum('count'),
                    'total_views' => (int) Article::sum('views_count'),
                ],
                'charts' => [
                    'views' => $viewsChart,
                    'articles' => $articlesChart,
                ],
                'top_articles' => ArticleResource::collection(
                    Article::query()
                        ->with(['cover', 'category', 'author.avatar'])
                        ->orderByDesc('views_count')
                        ->take(5)
                        ->get()
                ),
                'recent_activity' => ActivityLogResource::collection(
                    ActivityLog::query()
                        ->with('user.avatar')
                        ->latest()
                        ->take(8)
                        ->get()
                ),
                'role' => $request->user()->role->value,
                'is_editorial' => $request->user()->role->isEditorial(),
            ],
        ]);
    }
}
