<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'cover_media_id',
        'title', 'slug', 'excerpt', 'body', 'language', 'status',
        'is_featured', 'is_breaking',
        'meta_title', 'meta_description', 'source_name', 'source_url',
        'published_at', 'reviewed_by', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /* ----------------------------- scopes ---------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('is_breaking', true)
            ->where('published_at', '>=', now()->subDay());
    }

    public function scopeOfLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    /* --------------------------- attributes -------------------------- */

    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->body));

        return max(1, (int) ceil($words / 200));
    }

    public function getExcerptTextAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($this->body)));

        return mb_substr($text, 0, 200).(mb_strlen($text) > 200 ? '…' : '');
    }

    /* ---------------------------- relations -------------------------- */

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'article_media')
            ->withPivot('position')
            ->orderBy('article_media.position');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->where('status', \App\Enums\CommentStatus::Approved->value);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function bookmarkers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    /* ----------------------------------------------------------------- */

    public function syncTags(array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->filter()
            ->map(fn (string $name) => trim($name))
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name]
            )->id)
            ->all();

        $this->tags()->sync($tagIds);
    }
}
