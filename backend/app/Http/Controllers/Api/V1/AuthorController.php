<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\PublicAuthorResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    /**
     * Public author profile with their published work.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $author = User::query()
            ->whereKey($id)
            ->whereIn('role', ['super_admin', 'editor', 'author'])
            ->with('avatar')
            ->firstOrFail();

        $articles = $author->articles()
            ->published()
            ->with(['cover', 'category', 'author.avatar'])
            ->withCount('approvedComments')
            ->orderByDesc('published_at')
            ->paginate(min(24, (int) $request->query('per_page', 12)))
            ->withQueryString();

        return response()->json([
            'data' => new PublicAuthorResource($author),
            'articles' => ArticleResource::collection($articles),
        ]);
    }
}
