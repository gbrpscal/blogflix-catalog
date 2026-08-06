<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SearchMoviesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->query('query'))) {
            $query = preg_replace('/\s+/u', ' ', $this->query('query'));
            $this->merge(['query' => trim((string) $query)]);
        }
    }
}
