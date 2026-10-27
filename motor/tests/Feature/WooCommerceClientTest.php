<?php

use App\Application\Ports\StoreClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'store.url' => 'https://tienda.test',
        'store.key' => 'ck_test',
        'store.secret' => 'cs_test',
        'store.backoff_ms' => 0, // no real waiting in tests
    ]);
});

it('is bound to the WooCommerce adapter', function () {
    expect(app(StoreClient::class))->toBeInstanceOf(\App\Infrastructure\Store\WooCommerceClient::class);
});

it('calls the store with basic auth and returns data and page count', function () {
    Http::fake([
        'tienda.test/wp-json/wc/v3/products*' => Http::response([['id' => 1]], 200, ['X-WP-TotalPages' => '4']),
    ]);

    $result = app(StoreClient::class)->get('products', ['page' => 2]);

    expect($result)->toBe(['status' => 200, 'data' => [['id' => 1]], 'total_pages' => 4]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://tienda.test/wp-json/wc/v3/products?page=2'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('ck_test:cs_test')));
});

it('retries on a 5xx error and then succeeds', function () {
    Http::fake([
        'tienda.test/*' => Http::sequence()->push([], 503)->push([['id' => 1]], 200),
    ]);

    $result = app(StoreClient::class)->get('products');

    expect($result['status'])->toBe(200);
    Http::assertSentCount(2);
});

it('retries on a 429 error', function () {
    Http::fake([
        'tienda.test/*' => Http::sequence()->push([], 429)->push([], 200),
    ]);

    app(StoreClient::class)->get('products');

    Http::assertSentCount(2);
});

it('retries on a connection error', function () {
    Http::fake([
        'tienda.test/*' => Http::sequence()->pushFailedConnection()->push([], 200),
    ]);

    app(StoreClient::class)->get('products');

    Http::assertSentCount(2);
});

it('does not retry on other 4xx errors', function () {
    Http::fake(['tienda.test/*' => Http::response([], 404)]);

    expect(fn () => app(StoreClient::class)->get('products/999999'))->toThrow(RequestException::class);

    Http::assertSentCount(1);
});

it('gives up after three attempts', function () {
    Http::fake(['tienda.test/*' => Http::response([], 500)]);

    expect(fn () => app(StoreClient::class)->get('products'))->toThrow(RequestException::class);

    Http::assertSentCount(3);
});

it('records a successful call in the integration log without leaking the keys', function () {
    Http::fake(['tienda.test/*' => Http::response([], 200)]);

    app(StoreClient::class)->get('products');

    $log = IntegrationLog::sole();
    expect($log->direction->value)->toBe('outgoing')
        ->and($log->system->value)->toBe('store')
        ->and($log->operation)->toBe('get_products')
        ->and($log->status->value)->toBe('success')
        ->and($log->http_code)->toBe(200)
        ->and($log->attempts)->toBe(1)
        ->and(json_encode($log->toArray()))->not->toContain('cs_test')->not->toContain('ck_test');
});

it('records a failed call with its attempts', function () {
    Http::fake(['tienda.test/*' => Http::response([], 500)]);

    expect(fn () => app(StoreClient::class)->get('products/12'))->toThrow(RequestException::class);

    $log = IntegrationLog::sole();
    expect($log->operation)->toBe('get_products')
        ->and($log->status->value)->toBe('failure')
        ->and($log->http_code)->toBe(500)
        ->and($log->attempts)->toBe(3)
        ->and($log->error)->toBe(RequestException::class);
});
