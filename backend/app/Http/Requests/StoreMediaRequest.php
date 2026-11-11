<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file',
                'image', 'mimes:jpg,jpeg,png,gif,webp',
                'max:4096',
            ],
            'alt' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:500'],
            'credit' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.image' => 'Only image files can be uploaded.',
            'file.mimes' => 'Allowed image types: jpg, png, gif, webp.',
            'file.max' => 'The image may not be larger than 4 MB.',
        ];
    }
}
