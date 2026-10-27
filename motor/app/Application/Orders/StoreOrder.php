<?php

namespace App\Application\Orders;

use App\Models\Order;
use Carbon\CarbonImmutable;

/**
 * Creates or updates the engine's copy of an order from a WooCommerce webhook payload.
 *
 * Only what the engine needs is kept. The customer's name, email and phone are never
 * read, so they cannot leak through the API (contract C8).
 */
class StoreOrder
{
    /** The time comes in as a parameter, so a test controls it. */
    public function handle(array $payload, CarbonImmutable $now): Order
    {
        $order = Order::firstOrNew(['woo_id' => (int) $payload['id']]);
        $addressText = $this->addressText($payload['shipping'] ?? []);

        // A new order starts unprocessed; a changed address must be normalized again.
        $resetAddress = ! $order->exists || $order->address_text !== $addressText;

        $order->fill([
            'status' => (string) ($payload['status'] ?? 'pending'),
            'currency' => (string) ($payload['currency'] ?? 'CRC'),
            'total' => $payload['total'] ?? 0,
            'lines' => $this->lines($payload['line_items'] ?? []),
            'address_text' => $addressText,
            'source_updated_at' => $this->date($payload['date_modified_gmt'] ?? null, $now),
            'synced_at' => $now,
        ]);

        if (! $order->exists) {
            $order->ordered_at = $this->date($payload['date_created_gmt'] ?? null, $now);
        }

        if ($resetAddress) {
            $order->address_status = 'unprocessed';
            $order->address_normalized = null;
        }

        $order->save();

        return $order;
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
        return $value ? CarbonImmutable::parse($value, 'UTC') : $fallback;
    }
}
