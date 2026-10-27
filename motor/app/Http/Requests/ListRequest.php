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
            'pagina.integer' => 'Debe ser un entero mayor o igual que 1.',
            'pagina.min' => 'Debe ser un entero mayor o igual que 1.',
            'por_pagina.integer' => "Debe ser un entero entre 1 y {$max}.",
            'por_pagina.min' => "Debe ser un entero entre 1 y {$max}.",
            'por_pagina.max' => "Debe ser un entero entre 1 y {$max}.",
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