<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'user_id', 'file_name', 'disk', 'path', 'mime_type',
        'size', 'width', 'height', 'alt', 'caption', 'credit',
    ];

    protected $appends = ['url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $media) {
            Storage::disk($media->disk)->delete($media->path);
        });
    }
}
