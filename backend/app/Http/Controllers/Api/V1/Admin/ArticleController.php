<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Http\Requests\ArticleStatusRequest;
use App\Http\Resources\ArticleDetailResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticleStatusChanged;
use App\Services\ActivityLogger;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly HtmlSanitizer $sanitizer,
    ) {
    }

    /**
     * All articles (any status) for the editorial workspace.
     */
    public function index(Request $request)
    {
        $query = Article::query()
            ->with(['cover', 'category', 'author.avatar'])
            ->withCount('approvedComments');

        $query
            ->when(
                $request->filled('status'),
                fn (Builder $q) => $q->where('status', $request->query('status'))
            )
            ->when(
                $request->filled('author'),
                fn (Builder $q) => $q->where('user_id', (int) $request->query('author'))
            )
            ->when(
                $request->filled('category'),
                fn (Builder $q) => $q->whereHas(
                    'category',
                    fn (Builder $c) => $c->where('slug', $request->query('category'))
                )
            );

        // Authors only see their own articles.
        if (! $request->user()->role->isEditorial()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('q')) {
            $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $request->query('q')) . '%';

            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $term)
                ->orWhere('excerpt', 'like', $term));
        }

        $query->when(
            $request->query('sort') === 'oldest',
            fn (Builder $q) => $q->oldest(),
            fn (Builder $q) => $q->latest()
        );

        return ArticleResource::collection(
            $query->paginate(min(50, (int) $request->query('per_page', 20)))->withQueryString()
        );
    }

    public function store(ArticleRequest $request): JsonResponse
    {
        $this->authorize('create', Article::class);

        $data = $this->prepareData($request, new Article());

        $article = DB::transaction(function () use ($request, $data) {
            $article = Article::create($data);

            if ($request->filled('tags')) {
                $article->syncTags($request->input('tags', []));
            }

            if ($request->filled('gallery')) {
                $this->syncGallery($article, $request->input('gallery', []));
            }

            return $article;
        });

        $this->logger->log('article.created', 'Created article "' . $article->title . '"', $article);

        $article->load(['cover', 'category', 'author.avatar', 'tags', 'gallery']);

        return (new ArticleDetailResource($article))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Article $article): ArticleDetailResource
    {
        $this->authorize('view', $article);

        $article->load(['cover', 'category', 'author.avatar', 'tags', 'gallery']);

        return new ArticleDetailResource($article);
    }

    public function update(ArticleRequest $request, Article $article): ArticleDetailResource
    {
        $this->authorize('update', $article);

        $data = $this->prepareData($request, $article);

        DB::transaction(function () use ($request, $article, $data) {
            $article->update($data);

            if ($request->has('tags')) {
                $article->syncTags($request->input('tags', []));
            }

            if ($request->has('gallery')) {
                $this->syncGallery($article, $request->input('gallery', []));
            }
        });

        $this->logger->log('article.updated', 'Updated article "' . $article->title . '"', $article);

        $article->refresh()->load(['cover', 'category', 'author.avatar', 'tags', 'gallery']);

        return new ArticleDetailResource($article);
    }

    public function destroy(Request $request, Article $article): Response
    {
        $this->authorize('delete', $article);

        $title = $article->title;
        $article->delete();

        $this->logger->log('article.deleted', 'Deleted article "' . $title . '"');

        return response()->noContent();
    }

    /**
     * Editorial workflow transitions: submit, publish, schedule, reject, archive.
     */
    public function changeStatus(ArticleStatusRequest $request, Article $article): ArticleResource
    {
        $user = $request->user();
        $target = ArticleStatus::from($request->input('status'));

        $this->assertTransitionAllowed($user, $article, $target);

        DB::transaction(function () use ($request, $user, $article, $target) {
            $note = $request->input('review_note');

            switch ($target) {
                case ArticleStatus::Published:
                    $article->forceFill([
                        'status' => ArticleStatus::Published,
                        'published_at' => $article->published_at ?? now(),
                        'reviewed_by' => $user->id,
                        'review_note' => $note,
                    ])->save();

                    $this->notifyAuthor($article, $user, 'approved', $note);
                    $this->logger->log('article.published', 'Published "' . $article->title . '"', $article);

                    break;

                case ArticleStatus::Scheduled:
                    $publishedAt = $request->date('published_at');

                    if (! $publishedAt || $publishedAt->isPast()) {
                        throw ValidationException::withMessages([
                            'published_at' => 'A future publish date is required to schedule an article.',
                        ]);
                    }

                    $article->forceFill([
                        'status' => ArticleStatus::Scheduled,
                        'published_at' => $publishedAt,
                        'reviewed_by' => $user->id,
                        'review_note' => $note,
                    ])->save();

                    $this->notifyAuthor($article, $user, 'scheduled', $note);
                    $this->logger->log('article.scheduled', 'Scheduled "' . $article->title . '"', $article);

                    break;

                case ArticleStatus::Review:
                    $article->forceFill([
                        'status' => ArticleStatus::Review,
                    ])->save();

                    User::query()
                        ->whereIn('role', ['super_admin', 'editor'])
                        ->get()
                        ->each
                        ->notify(new ArticleStatusChanged($article, 'submitted'));

                    $this->logger->log('article.submitted', 'Submitted "' . $article->title . '" for review', $article);

                    break;

                case ArticleStatus::Draft:
                    $isRejection = $article->status === ArticleStatus::Review && $note !== null;

                    $article->forceFill([
                        'status' => ArticleStatus::Draft,
                        'reviewed_by' => $user->id,
                        'review_note' => $note,
                    ])->save();

                    if ($isRejection) {
                        $this->notifyAuthor($article, $user, 'rejected', $note);
                        $this->logger->log('article.rejected', 'Sent "' . $article->title . '" back to drafts', $article);
                    } else {
                        $this->logger->log('article.unpublished', 'Reverted "' . $article->title . '" to draft', $article);
                    }

                    break;

                case ArticleStatus::Archived:
                    $article->forceFill([
                        'status' => ArticleStatus::Archived,
                        'reviewed_by' => $user->id,
                    ])->save();

                    $this->logger->log('article.archived', 'Archived "' . $article->title . '"', $article);

                    break;
            }
        });

        $article->refresh()->load(['cover', 'category', 'author.avatar', 'tags', 'gallery']);

        return new ArticleResource($article);
    }

    /* ----------------------------------------------------------------- */

    private function prepareData(ArticleRequest $request, Article $article): array
    {
        $user = $request->user();
        $isEditorial = $user->role->isEditorial();

        $data = [
            'title' => trim($request->input('title')),
            'slug' => $this->uniqueSlug($request->input('slug') ?: $request->input('title'), $article->id),
            'excerpt' => $request->input('excerpt'),
            'body' => $this->sanitizer->clean($request->input('body')),
            'category_id' => $request->input('category_id'),
            'cover_media_id' => $request->input('cover_media_id'),
            'language' => $request->input('language') ?? 'en',
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'source_name' => $request->input('source_name'),
            'source_url' => $request->input('source_url'),
        ];

        if ($isEditorial) {
            $data['is_featured'] = (bool) $request->input('is_featured');
            $data['is_breaking'] = (bool) $request->input('is_breaking');

            if ($request->filled('published_at')) {
                $data['published_at'] = $request->date('published_at');
            }

            if ($request->filled('status')) {
                $data['status'] = ArticleStatus::from($request->input('status'));
            }
        } elseif ($article->exists === false) {
            // Authors always start at draft or review.
            $data['status'] = $request->input('status') === 'review'
                ? ArticleStatus::Review
                : ArticleStatus::Draft;
            $data['user_id'] = $user->id;
        }

        if ($article->exists === false && ! isset($data['user_id'])) {
            $data['user_id'] = $user->id;
        }

        return $data;
    }

    private function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = Str::slug(mb_substr($base, 0, 200)) ?: Str::random(8);
        $original = $slug;
        $suffix = 2;

        $query = Article::query()->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ((clone $query)->exists()) {
            $slug = $original . '-' . $suffix;
            $suffix++;

            $query = Article::query()->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    private function syncGallery(Article $article, array $mediaIds): void
    {
        $article->gallery()->sync(
            collect($mediaIds)
                ->values()
                ->mapWithKeys(fn ($id, $index) => [$id => ['position' => $index]])
                ->all()
        );
    }

    private function assertTransitionAllowed(User $user, Article $article, ArticleStatus $target): void
    {
        if ($user->role->isEditorial()) {
            return;
        }

        // Authors may submit their own draft for review, or revert it to draft.
        $isOwner = $article->user_id === $user->id;

        $allowed = $isOwner && in_array($target, [ArticleStatus::Review, ArticleStatus::Draft], true);

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => 'You are not allowed to move this article to ' . $target->value . '.',
            ]);
        }
    }

    private function notifyAuthor(Article $article, User $actor, string $action, ?string $note): void
    {
        if ($article->user_id === $actor->id) {
            return;
        }

        $article->author?->notify(
            new ArticleStatusChanged($article, $action, $note)
        );
    }
}
