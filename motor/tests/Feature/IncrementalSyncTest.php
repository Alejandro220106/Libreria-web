<?php

use App\Models\Product;
use App\Models\SyncState;
use Illuminate\Console\Scheduling\Schedule;
use Tests\Support\RefreshDatabaseAsOwner;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabaseAsOwner::class);

beforeEach(function () {
    config([
        'store.url' => 'https://tienda.test',
        'store.key' => 'ck_test',
        'store.secret' => 'cs_test',
        'store.backoff_ms' => 0,
    ]);
});

function incrementalProduct(int $id): array
{
    return [
        'id' => $id,
        'sku' => "SKU-{$id}",
        'name' => "Book {$id}",
        'type' => 'simple',
        'price' => '5200',
        'stock_quantity' => 12,
        'categories' => [],
        'book_author' => '',
        'date_modified_gmt' => '2026-10-31T10:00:00',
    ];
}

function lastSync(): ?string
{
    return SyncState::where('name', 'products')->first()?->last_succeeded_at?->toIso8601String();
}

it('stores the start time of a successful full sync', function () {
    $this->travelTo('2026-11-01 12:00:00');
    Http::fake(['tienda.test/*' => Http::response([incrementalProduct(1)], 200, ['X-WP-TotalPages' => '1'])]);

    $this->artisan('tienda:sincronizar-productos')->assertSuccessful();

    expect(lastSync())->toBe('2026-11-01T12:00:00+00:00');
});

it('asks only for products modified after the last sync', function () {
    SyncState::create(['name' => 'products', 'last_succeeded_at' => '2026-10-31 08:30:00']);
    $this->travelTo('2026-11-01 12:00:00');
    Http::fake(['tienda.test/*' => Http::response([incrementalProduct(7)], 200, ['X-WP-TotalPages' => '1'])]);

    $this->artisan('tienda:sincronizar-productos', ['--incremental' => true])
        ->expectsOutput('Synced 1 products changed since 2026-10-31T08:30:00+00:00.')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request['modified_after'] === '2026-10-31T08:30:00'
        && $request['dates_are_gmt'] === 'true');
    expect(Product::where('woo_id', 7)->exists())->toBeTrue()
        ->and(lastSync())->toBe('2026-11-01T12:00:00+00:00');
});

it('runs a full sync when --incremental has no previous date', function () {
    Http::fake(['tienda.test/*' => Http::response([incrementalProduct(1)], 200, ['X-WP-TotalPages' => '1'])]);

    $this->artisan('tienda:sincronizar-productos', ['--incremental' => true])
        ->expectsOutput('No previous sync found, running a full sync.')
        ->expectsOutput('Synced 1 products.')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => ! isset($request['modified_after']));
    expect(lastSync())->not->toBeNull();
});

it('keeps the full sync as the default even when a date is stored', function () {
    SyncState::create(['name' => 'products', 'last_succeeded_at' => '2026-10-31 08:30:00']);
    Http::fake(['tienda.test/*' => Http::response([incrementalProduct(1)], 200, ['X-WP-TotalPages' => '1'])]);

    $this->artisan('tienda:sincronizar-productos')
        ->expectsOutput('Synced 1 products.')
        ->assertSuccessful();

    Http::assertNotSent(fn (Request $request) => isset($request['modified_after']));
});

it('does not move the stored date forward when a run fails', function () {
    SyncState::create(['name' => 'products', 'last_succeeded_at' => '2026-10-31 08:30:00']);
    $this->travelTo('2026-11-01 12:00:00');
    Http::fake([
        'tienda.test/wp-json/wc/v3/products?page=1*' => Http::response([incrementalProduct(1)], 200, ['X-WP-TotalPages' => '2']),
        'tienda.test/wp-json/wc/v3/products?page=2*' => Http::response(['code' => 'error'], 500),
    ]);

    expect(fn () => $this->artisan('tienda:sincronizar-productos', ['--incremental' => true])->run())
        ->toThrow(RequestException::class);

    expect(lastSync())->toBe('2026-10-31T08:30:00+00:00');
});

it('schedules the incremental sync without overlapping runs', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command, 'tienda:sincronizar-productos'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->command)->toContain('--incremental')
        ->and($events->first()->expression)->toBe('*/15 * * * *')
        ->and($events->first()->withoutOverlapping)->toBeTrue();
});
