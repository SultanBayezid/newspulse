<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicAuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'bio' => $this->bio,
            'avatar_url' => $this->avatar?->url,
        ];
    }
}
