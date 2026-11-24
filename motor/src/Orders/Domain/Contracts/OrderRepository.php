<?php

namespace Src\Orders\Domain\Contracts;

use Carbon\CarbonImmutable;
use Src\Orders\Domain\Entities\Order;

/**
 * Port to read and save the engine's copy of the orders. Each method says what changes,
 * so the use cases never build database rows themselves.
 */
interface OrderRepository
{
    public function find(int $id): ?Order;

    public function findByWooId(int $wooId): ?Order;

    /**
     * Creates the order or updates it from the store data.
     *
     * @param  array{status: string, currency: string, total: mixed, lines: list<array<string, mixed>>, addressText: string, sourceUpdatedAt: CarbonImmutable, syncedAt: CarbonImmutable}  $data
     * @param  CarbonImmutable  $orderedAt  Only saved when the order is new.
     * @param  bool  $resetAddress  True when the address must be normalized again.
     */
    public function saveFromStore(int $wooId, array $data, CarbonImmutable $orderedAt, bool $resetAddress): Order;

    /** @param  array{provincia: string, canton: string, distrito: string, otras_senas: string, confianza: float}  $address */
    public function markAddressNormalized(int $id, array $address): void;

    public function markAddressPendingReview(int $id): void;

    /** @param  string  $status  "success" or "failure". */
    public function saveConnectorResult(int $id, string $status, ?string $detail, ?string $reference, CarbonImmutable $at): void;
}
