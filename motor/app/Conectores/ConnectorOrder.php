<?php

namespace App\Conectores;

/**
 * What a connector receives about an order. Only data the engine already keeps: products,
 * amounts, dates and the shipping address. Never the customer's name, email or phone (contract C8).
 */
final readonly class ConnectorOrder
{
    /**
     * @param  list<array{sku: ?string, name: string, quantity: int, unit_price: mixed, subtotal: mixed}>  $lines
     * @param  array{provincia?: string, canton?: string, distrito?: string, otras_senas?: string, confianza?: float}|null  $address  Normalized address, when the AI produced a valid one.
     */
    public function __construct(
        public int $orderId,
        public string $total,
        public string $currency,
        public array $lines = [],
        public ?string $orderedAt = null,
        public string $status = '',
        public string $addressText = '',
        public ?array $address = null,
        public string $addressStatus = '',
    ) {}
}
