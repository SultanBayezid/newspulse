<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'article_id' => ['required', 'integer', Rule::exists('articles', 'id')],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('comments', 'id')->where('article_id', $this->input('article_id')),
            ],
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}
