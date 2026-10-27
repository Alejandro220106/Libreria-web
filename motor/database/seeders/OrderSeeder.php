<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->utc();

        $line = fn (string $sku, string $name, int $quantity, int $price) => [
            'sku' => $sku,
            'name' => $name,
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => $quantity * $price,
        ];

        // The Newman collection requests orders 1001 and 1002 by id, so the
        // ids are fixed here instead of being auto-incremented.
        $orders = [
            [
                'id' => 1001,
                'woo_id' => 1001,
                'status' => 'processing',
                'total' => 16900,
                'lines' => [
                    $line('CONTRATO-001', 'Cuentos de mi tía Panchita', 2, 5200),
                    $line('LIB-PRI-01', 'El principito', 1, 6500),
                ],
                'address_text' => '100 metros norte de la iglesia de Ciudad Quesada, casa verde',
                'address_status' => 'normalized',
                'address_normalized' => [
                    'provincia' => 'Alajuela',
                    'canton' => 'San Carlos',
                    'distrito' => 'Quesada',
                    'otras_senas' => '100 metros norte de la iglesia, casa verde',
                    'confianza' => 0.93,
                ],
                'connector_status' => 'success',
                'connector_detail' => 'sent',
                'connector_reference' => 'msg-88231',
                'ordered_at' => '2026-11-10 20:02:55',
            ],
            [
                'id' => 1002,
                'woo_id' => 1002,
                'status' => 'on-hold',
                'total' => 7800,
                'lines' => [$line('LIB-MAM-01', 'Mamita Yunai', 1, 7800)],
                'address_text' => 'donde siempre, frente a la pulpería',
                'address_status' => 'pending_review',
                'address_normalized' => null,
                'connector_status' => 'failure',
                'connector_detail' => null,
                'connector_reference' => null,
                'ordered_at' => '2026-11-10 21:10:00',
            ],
            [
                'id' => 1003,
                'woo_id' => 1003,
                'status' => 'pending',
                'total' => 14900,
                'lines' => [$line('LIB-QUI-01', 'El Quijote de la Mancha', 1, 14900)],
                'address_text' => 'Florencia de San Carlos, 200 m sur de la plaza',
                'address_status' => 'unprocessed',
                'address_normalized' => null,
                'connector_status' => 'pending',
                'connector_detail' => null,
                'connector_reference' => null,
                'ordered_at' => '2026-11-10 22:45:12',
            ],
        ];

        $rows = array_map(fn (array $order) => [
            'id' => $order['id'],
            'woo_id' => $order['woo_id'],
            'status' => $order['status'],
            'currency' => 'CRC',
            'total' => $order['total'],
            'lines' => json_encode($order['lines'], JSON_UNESCAPED_UNICODE),
            'address_text' => $order['address_text'],
            'address_status' => $order['address_status'],
            'address_normalized' => $order['address_normalized'] === null
                ? null
                : json_encode($order['address_normalized'], JSON_UNESCAPED_UNICODE),
            'connector_status' => $order['connector_status'],
            'connector_detail' => $order['connector_detail'],
            'connector_reference' => $order['connector_reference'],
            'connector_updated_at' => $order['connector_status'] === 'pending' ? null : $order['ordered_at'],
            'ordered_at' => $order['ordered_at'],
            'source_updated_at' => $order['ordered_at'],
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $orders);

        DB::table('orders')->upsert(
            $rows,
            ['id'],
            ['status', 'total', 'lines', 'address_text', 'address_status', 'address_normalized', 'connector_status', 'updated_at'],
        );
    }
}
