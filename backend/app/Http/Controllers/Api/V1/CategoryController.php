<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->when(! $request->boolean('all'), fn (Builder $q) => $q->active())
            ->ordered()
            ->whereNull('parent_id')
            ->withCount(['articles' => fn ($a) => $a->published()])
            ->with(['children' => fn ($q) => $q
                ->active()
                ->ordered()
                ->withCount(['articles' => fn ($a) => $a->published()])])
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug): CategoryResource
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->withCount(['articles' => fn (Builder $a) => $a->published()])
            ->with(['children' => fn ($q) => $q
                ->active()
                ->ordered()
                ->withCount(['articles' => fn ($a) => $a->published()])])
            ->firstOrFail();

        return new CategoryResource($category);
    }
}
