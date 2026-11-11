<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookmarkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $articles = $request->user()
            ->bookmarks()
            ->published()
            ->with(['cover', 'category', 'author.avatar'])
            ->withCount('approvedComments')
            ->orderByDesc('bookmarks.created_at')
            ->paginate(min(50, (int) $request->query('per_page', 12)));

        return ArticleResource::collection($articles);
    }

    /**
     * Toggle the bookmark on an article.
     */
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'article_id' => ['required', 'integer', 'exists:articles,id'],
        ]);

        $user = $request->user();

        if ($user->bookmarks()->where('article_id', $data['article_id'])->exists()) {
            $user->bookmarks()->detach($data['article_id']);

            return response()->json([
                'bookmarked' => false,
                'message' => 'Removed from your bookmarks.',
            ]);
        }

        $user->bookmarks()->attach($data['article_id']);

        return response()->json([
            'bookmarked' => true,
            'message' => 'Saved to your bookmarks.',
        ]);
    }
}
