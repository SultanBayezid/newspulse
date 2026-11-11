<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_human' => sprintf('%.1f KB', $this->size / 1024),
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'caption' => $this->caption,
            'credit' => $this->credit,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
