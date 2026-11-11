<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tags = Tag::query()
            ->withCount(['articles' => fn (Builder $q) => $q->published()])
            ->when(
                $request->filled('q'),
                fn (Builder $q) => $q->where('name', 'like', '%' . $request->query('q') . '%')
            )
            ->orderByDesc('articles_count')
            ->take((int) $request->query('limit', 100))
            ->get();

        return TagResource::collection($tags);
    }

    public function show(string $slug): TagResource
    {
        $tag = Tag::query()
            ->where('slug', $slug)
            ->withCount(['articles' => fn (Builder $q) => $q->published()])
            ->firstOrFail();

        return new TagResource($tag);
    }
}
