<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'article' => $this->whenLoaded('article', fn () => [
                'id' => $this->article->id,
                'title' => $this->article->title,
                'slug' => $this->article->slug,
            ]),
            'user' => new PublicAuthorResource($this->whenLoaded('user')),
            'parent_id' => $this->parent_id,
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'likes_count' => $this->reactions->count(),
            'liked' => $user ? $this->reactions->contains('user_id', $user->id) : false,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
