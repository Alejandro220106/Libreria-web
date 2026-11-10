<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Same as RefreshDatabase, but the migrations run as the schema owner (`pgsql_admin`):
 * the application role the tests query with cannot create or drop tables.
 */
trait RefreshDatabaseAsOwner
{
    use RefreshDatabase {
        migrateFreshUsing as private frameworkMigrateFreshUsing;
    }

    /** @return array<string, mixed> */
    protected function migrateFreshUsing(): array
    {
        return [...$this->frameworkMigrateFreshUsing(), '--database' => 'pgsql_admin'];
    }
}
