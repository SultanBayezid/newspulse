<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'ends_at' => ['nullable', 'date', 'after:now'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.id' => ['nullable', 'integer', 'exists:poll_options,id'],
            'options.*.label' => ['required', 'string', 'max:250'],
        ];
    }
}
