<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt_text,
            'cover_url' => $this->cover?->url,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => new PublicAuthorResource($this->whenLoaded('author')),
            'language' => $this->language,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_featured' => $this->is_featured,
            'is_breaking' => $this->is_breaking,
            'views_count' => (int) $this->views_count,
            'reading_time' => $this->reading_time,
            'comments_count' => $this->whenCounted('approvedComments'),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
