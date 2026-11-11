<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PageResource::collection(
            Page::query()
                ->published()
                ->select('id', 'title', 'slug', 'updated_at')
                ->orderBy('title')
                ->get()
        );
    }

    public function show(string $slug): PageResource
    {
        return new PageResource(
            Page::query()
                ->published()
                ->with('author.avatar')
                ->where('slug', $slug)
                ->firstOrFail()
        );
    }
}
