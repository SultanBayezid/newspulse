<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(Request $request)
    {
        $categories = Category::query()
            ->withCount('articles')
            ->with(['children' => fn ($q) => $q->withCount('articles')->orderBy('position')])
            ->whereNull('parent_id')
            ->ordered()
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): CategoryResource
    {
        $this->authorize('create', Category::class);

        $category = Category::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->input('slug') ?: $request->input('name')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->logger->log('category.created', 'Created category "' . $category->name . '"', $category);

        return new CategoryResource($category->loadCount('articles'));
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        $category->update([
            ...$request->validated(),
            'slug' => $request->filled('slug')
                ? $this->uniqueSlug($request->input('slug'), $category->id)
                : $category->slug,
        ]);

        $this->logger->log('category.updated', 'Updated category "' . $category->name . '"', $category);

        return new CategoryResource($category->fresh(['children'])->loadCount('articles'));
    }

    public function destroy(Request $request, Category $category): Response
    {
        $this->authorize('delete', $category);

        $name = $category->name;
        $category->delete();

        $this->logger->log('category.deleted', 'Deleted category "' . $name . '"');

        return response()->noContent();
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: Str::random(8);
        $original = $slug;
        $suffix = 2;

        while (Category::query()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original . '-' . $suffix++;
        }

        return $slug;
    }
}
