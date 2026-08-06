<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MovieSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchMoviesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'min:2', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'genre_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::enum(MovieSort::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->query('query'))) {
            $query = preg_replace('/\s+/u', ' ', $this->query('query'));
            $query = trim((string) $query);

            $this->merge(['query' => $query === '' ? null : $query]);
        }
    }
}
