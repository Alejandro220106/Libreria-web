<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the contract pagination parameters (C2) of any listing.
 * Listings with extra filters extend this class.
 */
class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $max = config('api.pagination.max_per_page');

        return [
            'pagina' => ['sometimes', 'integer', 'min:1'],
            'por_pagina' => ['sometimes', 'integer', 'min:1', 'max:'.$max],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = config('api.pagination.max_per_page');

        return [
            'pagina.integer' => __('api.fields.page'),
            'pagina.min' => __('api.fields.page'),
            'por_pagina.integer' => __('api.fields.per_page', ['max' => $max]),
            'por_pagina.min' => __('api.fields.per_page', ['max' => $max]),
            'por_pagina.max' => __('api.fields.per_page', ['max' => $max]),
        ];
    }

    public function page(): int
    {
        return (int) $this->query('pagina', 1);
    }

    public function perPage(): int
    {
        return (int) $this->query('por_pagina', config('api.pagination.default_per_page'));
    }
}