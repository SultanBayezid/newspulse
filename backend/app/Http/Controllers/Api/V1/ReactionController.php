<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    /**
     * Toggle a "like" reaction on an article or a comment.
     */
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['article', 'comment'])],
            'id' => ['required', 'integer'],
        ]);

        $model = match ($data['type']) {
            'article' => Article::query()->findOrFail($data['id']),
            'comment' => Comment::query()->findOrFail($data['id']),
        };

        $existing = Reaction::query()
            ->where('user_id', $request->user()->id)
            ->where('reactionable_type', $model->getMorphClass())
            ->where('reactionable_id', $model->getKey())
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'liked' => false,
                'likes_count' => $model->reactions()->count(),
            ]);
        }

        Reaction::create([
            'user_id' => $request->user()->id,
            'reactionable_type' => $model->getMorphClass(),
            'reactionable_id' => $model->getKey(),
            'type' => 'like',
        ]);

        return response()->json([
            'liked' => true,
            'likes_count' => $model->reactions()->count(),
        ]);
    }
}
