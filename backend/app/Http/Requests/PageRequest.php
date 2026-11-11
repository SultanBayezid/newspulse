<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => ['required', 'string', 'max:250'],
            'slug' => [
                'nullable', 'string', 'max:250', 'alpha_dash',
                Rule::unique('pages', 'slug')->ignore($pageId),
            ],
            'body' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:250'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
