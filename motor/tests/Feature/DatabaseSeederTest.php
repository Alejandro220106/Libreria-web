<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Src\IntegrationLog\Infrastructure\Persistence\Models\IntegrationLog;
use Src\Orders\Infrastructure\Persistence\Models\Order;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

// The seeders insert fixed ids. PostgreSQL does not move a sequence on an explicit id,
// so without the fix the next insert reuses low ids and collides once it reaches them.
it('moves each id sequence past the seeded rows', function (string $model) {
    $maxSeeded = (int) DB::table((new $model)->getTable())->max('id');

    $created = $model::factory()->create();

    expect($created->id)->toBeGreaterThan($maxSeeded);
})->with([
    'orders' => Order::class,
    'integration log' => IntegrationLog::class,
]);
