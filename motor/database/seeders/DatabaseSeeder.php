<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Tables whose seeders insert fixed ids. */
    private const TABLES_WITH_FIXED_IDS = ['products', 'orders', 'integration_logs'];

    /**
     * Seed the data the Newman collection expects.
     */
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
            IntegrationLogSeeder::class,
        ]);

        $this->syncIdSequences();
    }

    /**
     * PostgreSQL does not move a sequence when a row is inserted with an explicit id, so after
     * the fixtures the next insert would reuse low ids and collide when it reaches them.
     * This moves each sequence to the highest id.
     *
     * The highest id is read with the connection that inserted the rows (it sees them even
     * inside an open transaction). setval runs as the schema owner: the application role may
     * use sequences but not change them (least privilege). Sequences are not transactional,
     * so the change applies at once.
     */
    private function syncIdSequences(): void
    {
        $owner = DB::connection('pgsql_admin');

        if ($owner->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLES_WITH_FIXED_IDS as $table) {
            $maxId = DB::table($table)->max('id');

            // With an empty table the sequence restarts at 1 (is_called = false).
            $owner->select(
                "select setval(pg_get_serial_sequence(?, 'id'), ?, ?)",
                [$table, $maxId ?? 1, $maxId !== null],
            );
        }
    }
}
