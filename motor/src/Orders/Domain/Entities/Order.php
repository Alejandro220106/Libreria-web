<?php

namespace Src\Orders\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * What the use cases know about an order. It is plain PHP: it does not know the
 * database, so a use case can be read and tested without Eloquent.
 * Customer personal data (name, email, phone) is never part of it (contract C8).
 */
final readonly class Order
{
    /**
     * @param  list<array{sku: ?string, name: string, quantity: int, unit_price: mixed, subtotal: mixed}>  $lines
     * @param  array{provincia: string, canton: string, distrito: string, otras_senas: string, confianza: float}|null  $addressNormalized
     */
    public function __construct(
        public int $id,
        public int $wooId,
        public string $status,
        public string $currency,
        public string $total,
        public array $lines,
        public ?CarbonImmutable $orderedAt,
        public string $addressText,
        public string $addressStatus,
        public ?array $addressNormalized,
        public string $connectorStatus,
    ) {}
}
