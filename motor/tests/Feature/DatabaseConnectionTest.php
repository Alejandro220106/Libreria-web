<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('uses PostgreSQL as the default connection', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('accepts queries on the configured database', function () {
    expect(DB::scalar('select 1'))->toBe(1);
});

it('runs on PostgreSQL 18 or newer', function () {
    expect((int) DB::scalar('show server_version_num'))->toBeGreaterThanOrEqual(180000);
});

it('uses the testing database during tests', function () {
    expect(DB::connection()->getDatabaseName())->toEndWith('_testing');
});
