<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'is_published' => $this->is_published,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'author' => new PublicAuthorResource($this->whenLoaded('author')),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
