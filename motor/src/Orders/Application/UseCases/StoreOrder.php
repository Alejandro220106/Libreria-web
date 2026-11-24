<?php

namespace Src\Orders\Application\UseCases;

use Carbon\CarbonImmutable;
use Src\Orders\Domain\Contracts\OrderRepository;
use Src\Orders\Domain\Entities\Order;

/**
 * Creates or updates the engine's copy of an order from a WooCommerce webhook payload.
 *
 * Only what the engine needs is kept. The customer's name, email and phone are never
 * read, so they cannot leak through the API (contract C8).
 */
class StoreOrder
{
    public function __construct(private readonly OrderRepository $orders) {}

    /** The time comes in as a parameter, so a test controls it. */
    public function handle(array $payload, CarbonImmutable $now): Order
    {
        $wooId = (int) $payload['id'];
        $existing = $this->orders->findByWooId($wooId);
        $addressText = $this->addressText($payload['shipping'] ?? []);

        // A new order starts unprocessed; a changed address must be normalized again.
        $resetAddress = $existing === null || $existing->addressText !== $addressText;

        return $this->orders->saveFromStore(
            $wooId,
            [
                'status' => (string) ($payload['status'] ?? 'pending'),
                'currency' => (string) ($payload['currency'] ?? 'CRC'),
                'total' => $payload['total'] ?? 0,
                'lines' => $this->lines($payload['line_items'] ?? []),
                'addressText' => $addressText,
                'sourceUpdatedAt' => $this->date($payload['date_modified_gmt'] ?? null, $now),
                'syncedAt' => $now,
            ],
            orderedAt: $this->date($payload['date_created_gmt'] ?? null, $now),
            resetAddress: $resetAddress,
        );
    }

    /** Street and place only: no first name, last name or phone. */
    private function addressText(array $shipping): string
    {
        $parts = array_map(
            fn (string $key) => trim((string) ($shipping[$key] ?? '')),
            ['address_1', 'address_2', 'city', 'state'],
        );

        return implode(', ', array_filter($parts, fn (string $part) => $part !== ''));
    }

    private function lines(array $items): array
    {
        return array_map(fn (array $item) => [
            'sku' => $item['sku'] ?? null,
            'name' => (string) ($item['name'] ?? ''),
            'quantity' => (int) ($item['quantity'] ?? 1),
            'unit_price' => $item['price'] ?? 0,
            'subtotal' => $item['subtotal'] ?? 0,
        ], array_values(array_filter($items, 'is_array')));
    }

    private function date(?string $value, CarbonImmutable $fallback): CarbonImmutable
    {
        // WooCommerce sends the *_gmt fields without a zone: they are UTC.
        // The columns have no zone, so the date is stored in the app's zone.
        return $value
            ? CarbonImmutable::parse($value, 'UTC')->setTimezone(config('app.timezone'))
            : $fallback;
    }
}
