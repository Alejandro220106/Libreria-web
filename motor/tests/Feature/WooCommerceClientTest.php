<?php

use App\Application\Ports\StoreClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

it('records every call without leaking the keys', function () {
    Log::spy();
    Http::fake(['tienda.test/*' => Http::response([], 200)]);

    app(StoreClient::class)->get('products');

    Log::shouldHaveReceived('info')->once()->withArgs(function (string $message, array $context) {
        return $message === 'store.request'
            && $context['endpoint'] === 'products'
            && ! str_contains(json_encode($context), 'cs_test')
            && ! str_contains(json_encode($context), 'ck_test');
    });
});
