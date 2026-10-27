<?php

namespace App\Conectores;

/** The minimum a connector needs from an order. No customer personal data (contract C8). */
final readonly class ConnectorOrder
{
    public function __construct(
        public int $orderId,
        public string $total,
        public string $currency,
    ) {}
}
