<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One order in the contract shape (schema Pedido, C5).
 *
 * The database keeps English status values; the contract uses Spanish ones.
 * No customer name, email or phone is ever returned (C8).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    private const ADDRESS_STATUS = [
        'unprocessed' => 'sin_procesar',
        'normalized' => 'normalizada',
        'pending_review' => 'pendiente_revision',
    ];

    private const CONNECTOR_STATUS = [
        'pending' => 'pendiente',
        'success' => 'exito',
        'failure' => 'fallo',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // The contract identifies orders by their WooCommerce id.
            'id' => $this->woo_id,
            'estado' => $this->status,
            'moneda' => $this->currency,
            // Colones have no decimals.
            'total' => (int) round((float) $this->total),
            'creado_en' => $this->ordered_at->toIso8601String(),
            'lineas' => array_map(fn (array $line) => [
                'sku' => $line['sku'],
                'nombre' => $line['name'],
                'cantidad' => (int) $line['quantity'],
                'precio_unitario' => (int) round((float) $line['unit_price']),
                'subtotal' => (int) round((float) $line['subtotal']),
            ], $this->lines),
            'direccion' => [
                'estado' => self::ADDRESS_STATUS[$this->address_status],
                'texto' => $this->address_text,
                'normalizada' => $this->address_normalized,
            ],
            'conector' => [
                'estado' => self::CONNECTOR_STATUS[$this->connector_status],
                'detalle' => $this->connector_detail,
                'referencia_externa' => $this->connector_reference,
                'actualizado_en' => $this->connector_updated_at?->toIso8601String(),
            ],
        ];
    }
}
