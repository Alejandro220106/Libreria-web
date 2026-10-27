<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One product in the contract shape (schema Producto, C4).
 *
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_tienda' => $this->woo_id,
            'sku' => $this->sku,
            'nombre' => $this->name,
            'tipo' => $this->type,
            'precio' => $this->price,
            'existencias' => $this->stock,
            'categorias' => $this->categories,
            // The plugin's custom field: the book's author.
            'campo_propio' => ['nombre' => 'autor', 'valor' => $this->author],
            'sincronizado_en' => $this->synced_at?->toIso8601String(),
        ];
    }
}
