<?php

namespace Database\Seeders;

use App\Models\IntegrationLog;
use App\Models\Order;
use App\Models\Product;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Large dataset to measure the engine API (#81). Local use only.
 *
 * It is NOT called by DatabaseSeeder, so `php artisan migrate --seed` and the
 * Newman collection keep their small, fixed data. Run it on purpose with:
 *   php artisan db:seed --class=PerformanceSeeder
 */
class PerformanceSeeder extends Seeder
{
    private const PRODUCTS = 20000;

    private const ORDERS = 10000;

    private const LOGS = 50000;

    // One insert per chunk instead of one per row.
    private const CHUNK = 1000;

    // Store ids start far above the default seed (products 41-54, orders 1001-1003).
    private const PRODUCT_ID_OFFSET = 100000;

    private const ORDER_ID_OFFSET = 200000;

    public function run(): void
    {
        $now = now();

        $this->insert('products', self::PRODUCTS, fn (int $i) => Product::factory()->raw([
            'woo_id' => self::PRODUCT_ID_OFFSET + $i,
            'sku' => sprintf('PERF-%06d', $i),
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        $this->insert('orders', self::ORDERS, fn (int $i) => Order::factory()->raw([
            'woo_id' => self::ORDER_ID_OFFSET + $i,
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        $this->insert('integration_logs', self::LOGS, fn (int $i) => IntegrationLog::factory()->raw());
    }

    /**
     * @param  callable(int): array<string, mixed>  $row
     */
    private function insert(string $table, int $count, callable $row): void
    {
        for ($start = 1; $start <= $count; $start += self::CHUNK) {
            $rows = [];
            for ($i = $start; $i < $start + self::CHUNK && $i <= $count; $i++) {
                $rows[] = array_map($this->toColumn(...), $row($i));
            }

            DB::table($table)->insert($rows);
        }

        $this->command?->info("{$table}: {$count} rows");
    }

    /**
     * raw() skips the model casts, so arrays, enums and dates are converted here.
     */
    private function toColumn(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            default => $value,
        };
    }
}
