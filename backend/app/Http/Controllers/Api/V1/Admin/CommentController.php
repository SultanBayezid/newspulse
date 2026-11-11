<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Notifications\CommentSubmitted;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        return CommentResource::collection(
            Comment::query()
                ->with(['user.avatar', 'article' => fn ($q) => $q->select('id', 'title', 'slug'), 'reactions'])
                ->when(
                    $request->filled('status'),
                    fn ($q) => $q->where('status', $request->query('status'))
                )
                ->when(
                    $request->filled('q'),
                    fn ($q) => $q->where('body', 'like', '%' . $request->query('q') . '%')
                )
                ->latest()
                ->paginate(min(100, (int) $request->query('per_page', 25)))
                ->withQueryString()
        );
    }

    /**
     * Approve, reject or re-queue a comment.
     */
    public function updateStatus(Request $request, Comment $comment): CommentResource
    {
        $this->authorize('moderate', $comment);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $comment->update(['status' => $data['status']]);

        if ($data['status'] === 'approved') {
            $comment->user?->notify(new CommentSubmitted($comment->refresh()));
        }

        $this->logger->log(
            'comment.' . $data['status'],
            'Comment #' . $comment->id . ' marked as ' . $data['status'],
            $comment
        );

        return new CommentResource($comment->fresh(['user.avatar', 'article', 'reactions']));
    }

    public function destroy(Request $request, Comment $comment): Response
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        $this->logger->log('comment.deleted', 'Deleted comment #' . $comment->id);

        return response()->noContent();
    }
}
