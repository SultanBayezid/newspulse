<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['draft', 'review', 'scheduled', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
