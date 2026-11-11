<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentSubmitted;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * Approved comments for an article (threaded, one level of replies).
     */
    public function index(Article $article): AnonymousResourceCollection
    {
        $comments = $article->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with([
                'user.avatar',
                'replies.user.avatar',
                'replies.reactions',
                'reactions',
            ])
            ->oldest()
            ->take(200)
            ->get();

        return CommentResource::collection($comments);
    }

    /**
     * Post a comment (moderated according to settings).
     */
    public function store(CommentRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_if($user->isBanned(), 403, 'Your account has been suspended.');

        if (! $this->settings->bool('comments_enabled', true)) {
            return response()->json([
                'message' => 'Comments are currently disabled.',
            ], 403);
        }

        $moderation = $this->settings->bool('comments_moderation', true);

        $comment = Comment::create([
            'article_id' => $request->input('article_id'),
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'body' => trim(strip_tags($request->input('body'))),
            'status' => $moderation ? 'pending' : 'approved',
        ]);

        if ($moderation) {
            User::query()
                ->whereIn('role', ['super_admin', 'editor'])
                ->get()
                ->each
                ->notify(new CommentSubmitted($comment));
        }

        $comment->load('user.avatar', 'replies', 'reactions');

        return response()->json([
            'data' => new CommentResource($comment),
            'message' => $moderation
                ? 'Your comment has been submitted and is awaiting moderation.'
                : 'Your comment has been posted.',
        ], 201);
    }
}
