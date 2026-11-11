<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        return TagResource::collection(
            Tag::query()
                ->withCount('articles')
                ->when(
                    $request->filled('q'),
                    fn ($q) => $q->where('name', 'like', '%' . $request->query('q') . '%')
                )
                ->orderByDesc('articles_count')
                ->paginate(min(100, (int) $request->query('per_page', 50)))
                ->withQueryString()
        );
    }

    public function store(TagRequest $request): TagResource
    {
        $this->authorize('create', Tag::class);

        $tag = Tag::firstOrCreate(
            ['slug' => Str::slug($request->input('slug') ?: $request->input('name'))],
            ['name' => $request->input('name')]
        );

        $this->logger->log('tag.created', 'Created tag "' . $tag->name . '"', $tag);

        return new TagResource($tag->loadCount('articles'));
    }

    public function update(TagRequest $request, Tag $tag): TagResource
    {
        $this->authorize('update', $tag);

        $tag->update([
            'name' => $request->input('name'),
            'slug' => $request->filled('slug')
                ? Str::slug($request->input('slug'))
                : $tag->slug,
        ]);

        $this->logger->log('tag.updated', 'Updated tag "' . $tag->name . '"', $tag);

        return new TagResource($tag->loadCount('articles'));
    }

    public function destroy(Request $request, Tag $tag): Response
    {
        $this->authorize('delete', $tag);

        $name = $tag->name;
        $tag->delete();

        $this->logger->log('tag.deleted', 'Deleted tag "' . $name . '"');

        return response()->noContent();
    }
}
