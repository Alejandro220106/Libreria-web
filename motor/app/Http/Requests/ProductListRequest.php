<?php

namespace App\Http\Requests;

/**
 * GET /productos: contract pagination plus the exact-SKU filter.
 */
class ProductListRequest extends ListRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'sku' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
