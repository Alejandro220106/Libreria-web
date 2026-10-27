<?php

use App\Application\Orders\RunConnectorAction;
use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\ConnectorResult;
use App\Models\IntegrationLog;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'connector.provider' => 'mailtrap',
        'connector.backoff_ms' => 0,
        'connector.mailtrap.token' => 'mt_secret_token',
        'connector.mailtrap.inbox_id' => '555',
    ]);
});

function newOrder(array $overrides = []): Order
{
    return Order::create(array_merge([
        'woo_id' => 2001,
        'status' => 'processing',
        'currency' => 'CRC',
        'total' => 5200,
        'lines' => [['sku' => 'LIB-01', 'quantity' => 1]],
        'address_text' => '100 metros norte de la iglesia de Ciudad Quesada',
        'ordered_at' => now()->utc(),
    ], $overrides));
}

it('runs the connector on a new order and stores the result', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['success' => true, 'message_ids' => ['abc-1']])]);
    $order = newOrder();

    app(RunConnectorAction::class)->handle($order->id);

    $order->refresh();
    expect($order->connector_status)->toBe('success')
        ->and($order->connector_reference)->toBe('abc-1')
        ->and($order->connector_updated_at)->not->toBeNull();
});

it('keeps the order saved and logs the failure when the provider is down', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response([], 500)]);
    $order = newOrder();

    app(RunConnectorAction::class)->handle($order->id);

    $order->refresh();
    expect(Order::count())->toBe(1)
        ->and($order->connector_status)->toBe('failure')
        ->and($order->connector_detail)->toBe('The provider answered 500');

    $log = IntegrationLog::latest('id')->first();
    expect($log->system->value)->toBe('connector')->and($log->status->value)->toBe('failure');
});

it('does not notify the same order twice when the job is repeated', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['message_ids' => ['abc-1']])]);
    $order = newOrder();

    app(RunConnectorAction::class)->handle($order->id);
    app(RunConnectorAction::class)->handle($order->id);

    Http::assertSentCount(1);
});

it('retries an order whose previous attempt failed', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['message_ids' => ['abc-2']])]);
    $order = newOrder(['connector_status' => 'failure']);

    app(RunConnectorAction::class)->handle($order->id);

    expect($order->refresh()->connector_status)->toBe('success');
});

it('ignores an order that does not exist', function () {
    app(RunConnectorAction::class)->handle(999999);

    Http::assertNothingSent();
});

it('turns an unexpected connector exception into a failure', function () {
    app()->bind(ConnectorInterface::class, fn () => new class implements ConnectorInterface
    {
        public function execute(ConnectorOrder $order): ConnectorResult
        {
            throw new RuntimeException('boom');
        }
    });
    $order = newOrder();

    app(RunConnectorAction::class)->handle($order->id);

    expect($order->refresh()->connector_status)->toBe('failure')
        ->and($order->connector_detail)->toBe('Unexpected connector error')
        ->and(IntegrationLog::count())->toBe(1);
});
