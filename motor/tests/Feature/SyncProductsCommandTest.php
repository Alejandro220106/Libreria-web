<?php

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

beforeEach(function () {
    config([
        'store.url' => 'https://tienda.test',
        'store.key' => 'ck_test',
        'store.secret' => 'cs_test',
        'store.backoff_ms' => 0,
    ]);
});

function storeProduct(int $id, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'sku' => "SKU-{$id}",
        'name' => "Book {$id}",
        'type' => 'simple',
        'price' => '5200',
        'stock_quantity' => 12,
        'categories' => [['id' => 1, 'name' => 'Novela']],
        'book_author' => 'Carmen Lyra',
        'date_modified_gmt' => '2026-10-20T15:30:00',
    ], $overrides);
}

it('walks every page and saves each product with its custom field', function () {
    Http::fake([
        'tienda.test/wp-json/wc/v3/products?page=1*' => Http::response([storeProduct(1), storeProduct(2)], 200, ['X-WP-TotalPages' => '2']),
        'tienda.test/wp-json/wc/v3/products?page=2*' => Http::response([storeProduct(3)], 200, ['X-WP-TotalPages' => '2']),
    ]);

    $this->artisan('tienda:sincronizar-productos')
        ->expectsOutput('Synced 3 products.')
        ->assertSuccessful();

    expect(Product::count())->toBe(3);

    $product = Product::where('woo_id', 1)->first();
    expect($product->name)->toBe('Book 1')
        ->and($product->price)->toBe(5200)
        ->and($product->stock)->toBe(12)
        ->and($product->categories)->toBe(['Novela'])
        ->and($product->author)->toBe('Carmen Lyra')
        ->and($product->source_updated_at->toIso8601String())->toBe('2026-10-20T09:30:00-06:00')
        ->and($product->synced_at)->not->toBeNull();
});

it('does not duplicate products when it runs twice and updates the changed ones', function () {
    Http::fake([
        'tienda.test/*' => Http::sequence()
            ->push([storeProduct(1)], 200, ['X-WP-TotalPages' => '1'])
            ->push([storeProduct(1, ['name' => 'Renamed', 'price' => '6000'])], 200, ['X-WP-TotalPages' => '1']),
    ]);

    $this->artisan('tienda:sincronizar-productos')->assertSuccessful();
    $this->artisan('tienda:sincronizar-productos')->assertSuccessful();

    expect(Product::count())->toBe(1)
        ->and(Product::first()->name)->toBe('Renamed')
        ->and(Product::first()->price)->toBe(6000);
});

it('stores empty store values as null and maps the product type', function () {
    Http::fake([
        'tienda.test/*' => Http::response([
            storeProduct(1, ['sku' => '', 'price' => '', 'stock_quantity' => null, 'book_author' => '', 'type' => 'grouped']),
            storeProduct(2, ['sku' => '', 'type' => 'variable']),
        ], 200, ['X-WP-TotalPages' => '1']),
    ]);

    $this->artisan('tienda:sincronizar-productos')->assertSuccessful();

    $grouped = Product::where('woo_id', 1)->first();
    expect($grouped->sku)->toBeNull()
        ->and($grouped->price)->toBeNull()
        ->and($grouped->stock)->toBeNull()
        ->and($grouped->author)->toBeNull()
        ->and($grouped->type)->toBe('agrupado')
        ->and(Product::where('woo_id', 2)->first()->sku)->toBeNull();
});

it('logs the store calls it makes', function () {
    Http::fake(['tienda.test/*' => Http::response([storeProduct(1)], 200, ['X-WP-TotalPages' => '1'])]);

    $this->artisan('tienda:sincronizar-productos')->assertSuccessful();

    $this->assertDatabaseHas('integration_logs', ['system' => 'store', 'operation' => 'get_products', 'status' => 'success']);
});
