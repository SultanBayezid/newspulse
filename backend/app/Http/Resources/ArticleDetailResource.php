<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleDetailResource extends ArticleResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $user = $request->user();

        return array_merge($data, [
            'body' => $this->body,
            'cover_media_id' => $this->cover_media_id,
            'raw_excerpt' => $this->excerpt,
            'gallery' => $this->whenLoaded('gallery', fn () => $this->gallery->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->url,
                'alt' => $media->alt,
                'caption' => $media->caption,
                'credit' => $media->credit,
            ])->values()->all()),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'review_note' => $this->review_note,
            'reviewed_by' => $this->reviewed_by,
            'source_name' => $this->source_name,
            'source_url' => $this->source_url,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'likes_count' => (int) $this->reactions()->count(),
            'liked' => $user
                ? $this->reactions()->where('user_id', $user->id)->exists()
                : false,
            'bookmarked' => $user
                ? $this->bookmarkers()->where('users.id', $user->id)->exists()
                : false,
        ]);
    }
}
