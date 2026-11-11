<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\ActivityLogger;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly HtmlSanitizer $sanitizer,
    ) {
    }

    public function index(Request $request)
    {
        return PageResource::collection(
            Page::query()
                ->with('author')
                ->when(
                    $request->filled('q'),
                    fn ($q) => $q->where('title', 'like', '%' . $request->query('q') . '%')
                )
                ->orderBy('title')
                ->paginate(min(50, (int) $request->query('per_page', 20)))
                ->withQueryString()
        );
    }

    public function store(PageRequest $request): PageResource
    {
        $this->authorize('create', Page::class);

        $page = Page::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'body' => $this->sanitizer->clean($request->input('body')),
            'slug' => $this->uniqueSlug($request->input('slug') ?: $request->input('title')),
            'is_published' => $request->boolean('is_published', true),
        ]);

        $this->logger->log('page.created', 'Created page "' . $page->title . '"', $page);

        return new PageResource($page->load('author'));
    }

    public function update(PageRequest $request, Page $page): PageResource
    {
        $this->authorize('update', $page);

        $page->update([
            ...$request->validated(),
            'body' => $this->sanitizer->clean($request->input('body')),
            'slug' => $request->filled('slug')
                ? $this->uniqueSlug($request->input('slug'), $page->id)
                : $page->slug,
        ]);

        $this->logger->log('page.updated', 'Updated page "' . $page->title . '"', $page);

        return new PageResource($page->fresh('author'));
    }

    public function destroy(Request $request, Page $page): Response
    {
        $this->authorize('delete', $page);

        $title = $page->title;
        $page->delete();

        $this->logger->log('page.deleted', 'Deleted page "' . $title . '"');

        return response()->noContent();
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: Str::random(8);
        $original = $slug;
        $suffix = 2;

        while (Page::query()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original . '-' . $suffix++;
        }

        return $slug;
    }
}
