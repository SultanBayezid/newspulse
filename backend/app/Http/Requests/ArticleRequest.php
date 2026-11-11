<?php

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $articleId = $this->route('article')?->id;

        return [
            'title' => ['required', 'string', 'max:250'],
            'slug' => [
                'nullable', 'string', 'max:250', 'alpha_dash',
                Rule::unique('articles', 'slug')->ignore($articleId),
            ],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => ['integer', 'exists:media,id'],
            'tags' => ['nullable', 'array', 'max:15'],
            'tags.*' => ['string', 'max:60'],
            'language' => ['nullable', Rule::in(['en', 'bn'])],
            'status' => ['nullable', Rule::in(array_column(ArticleStatus::cases(), 'value'))],
            'is_featured' => ['nullable', 'boolean'],
            'is_breaking' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:250'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'This slug is already in use by another article.',
            'source_url.url' => 'The source URL must be a valid URL.',
        ];
    }
}
