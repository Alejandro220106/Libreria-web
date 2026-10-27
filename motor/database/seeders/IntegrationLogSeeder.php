<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IntegrationLogSeeder extends Seeder
{
    public function run(): void
    {
        // direction, system, operation, status, http_code, attempts, duration_ms, correlation_id, error
        $template = [
            ['incoming', 'store', 'order_created_webhook', 'success', 200, 1, 15, 'contrato-entrega-001', null],
            ['outgoing', 'connector', 'run_action', 'success', 200, 1, 412, '1001', null],
            ['outgoing', 'ai', 'normalize_address', 'success', 200, 1, 1830, '1001', null],
            ['incoming', 'store', 'order_created_webhook', 'duplicate', 200, 1, 9, 'contrato-entrega-001', null],
            ['incoming', 'store', 'order_created_webhook', 'success', 200, 1, 14, 'contrato-entrega-002', null],
            ['outgoing', 'connector', 'run_action', 'failure', 503, 3, 9120, '1002', 'The provider answered 503 on the three attempts'],
            ['outgoing', 'ai', 'normalize_address', 'failure', 200, 1, 2210, '1002', 'The output does not match the schema: district is missing'],
            ['incoming', 'store', 'order_created_webhook', 'invalid_signature', 401, 1, 4, 'contrato-entrega-003', 'The signature does not match'],
            ['outgoing', 'store', 'list_products', 'success', 200, 1, 640, null, null],
            ['outgoing', 'store', 'list_products', 'failure', null, 3, 30050, null, 'Timeout'],
        ];

        $newest = CarbonImmutable::parse('2026-11-10 21:59:00', 'UTC');
        $total = 24;
        $rows = [];

        // The newest entry gets the highest id, so ordering by date and by id agree.
        for ($i = 0; $i < $total; $i++) {
            [$direction, $system, $operation, $status, $code, $attempts, $duration, $correlation, $error] = $template[$i % count($template)];

            $rows[] = [
                'id' => $total - $i,
                'direction' => $direction,
                'system' => $system,
                'operation' => $operation,
                'status' => $status,
                'http_code' => $code,
                'attempts' => $attempts,
                'duration_ms' => $duration,
                'correlation_id' => $correlation,
                'error' => $error,
                'logged_at' => $newest->subMinutes($i * 2)->toDateTimeString(),
            ];
        }

        DB::table('integration_logs')->upsert(
            $rows,
            ['id'],
            ['direction', 'system', 'operation', 'status', 'http_code', 'attempts', 'duration_ms', 'correlation_id', 'error', 'logged_at'],
        );
    }
}
