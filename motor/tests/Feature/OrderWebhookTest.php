<?php

use App\Application\Orders\RunConnectorAction;
use App\Jobs\ProcessOrder;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'store.webhook_secret' => 'whsec_test',
        'connector.provider' => 'mailtrap',
        'connector.backoff_ms' => 0,
        'connector.mailtrap.token' => 'mt_secret_token',
        'connector.mailtrap.inbox_id' => '555',
    ]);
});

function orderPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 3001,
        'status' => 'processing',
        'currency' => 'CRC',
        'total' => '16900.00',
        'date_created_gmt' => '2026-11-10T20:02:55',
        'date_modified_gmt' => '2026-11-10T20:03:10',
        'line_items' => [
            ['sku' => 'CONTRATO-001', 'name' => 'Cuentos de mi tía Panchita', 'quantity' => 2, 'price' => 5200, 'subtotal' => '10400.00'],
        ],
        'shipping' => [
            'first_name' => 'Ana', 'last_name' => 'Mora', 'phone' => '8888-0000',
            'address_1' => '100 metros norte de la iglesia', 'address_2' => 'casa verde',
            'city' => 'Ciudad Quesada', 'state' => 'Alajuela',
        ],
        'billing' => ['email' => 'ana@ejemplo.com'],
    ], $overrides);
}

function sendOrder(array $payload, string $deliveryId = 'delivery-1')
{
    $body = json_encode($payload);

    return test()->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode(hash_hmac('sha256', $body, 'whsec_test', true)),
        'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => $deliveryId,
        'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.created',
    ], $body);
}

it('saves the order and answers 200 with a valid signature', function () {
    Bus::fake();

    sendOrder(orderPayload())->assertOk();

    $order = Order::sole();
    expect($order->woo_id)->toBe(3001)
        ->and($order->status)->toBe('processing')
        ->and($order->total)->toBe('16900.00')
        ->and($order->lines[0]['sku'])->toBe('CONTRATO-001')
        ->and($order->address_text)->toBe('100 metros norte de la iglesia, casa verde, Ciudad Quesada, Alajuela')
        ->and($order->address_status)->toBe('unprocessed')
        ->and($order->ordered_at->toDateTimeString())->toBe('2026-11-10 20:02:55');
});

it('never stores the customer personal data', function () {
    Bus::fake();

    sendOrder(orderPayload())->assertOk();

    $stored = json_encode(Order::sole()->toArray());
    expect($stored)->not->toContain('Ana')
        ->and($stored)->not->toContain('Mora')
        ->and($stored)->not->toContain('8888-0000')
        ->and($stored)->not->toContain('ana@ejemplo.com');
});

it('updates the same order on a second delivery without losing the connector result', function () {
    Bus::fake();
    sendOrder(orderPayload(), 'delivery-1')->assertOk();
    Order::sole()->update(['connector_status' => 'success', 'connector_reference' => 'abc-1']);

    sendOrder(orderPayload(['status' => 'completed']), 'delivery-2')->assertOk();

    $order = Order::sole();
    expect($order->status)->toBe('completed')
        ->and($order->connector_status)->toBe('success')
        ->and($order->connector_reference)->toBe('abc-1');
});

it('dispatches the processing job after the response with the order id, not the model', function () {
    Bus::fake();

    sendOrder(orderPayload())->assertOk();

    Bus::assertDispatchedAfterResponse(ProcessOrder::class, fn (ProcessOrder $job) => $job->orderId === Order::sole()->id);
});

it('does not dispatch the job for a duplicate delivery', function () {
    Bus::fake();

    sendOrder(orderPayload())->assertOk();
    sendOrder(orderPayload())->assertOk();

    Bus::assertDispatchedAfterResponseTimes(ProcessOrder::class, 1);
});

it('runs the connector after the response', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['message_ids' => ['abc-1']])]);

    sendOrder(orderPayload())->assertOk();

    expect(Order::sole()->connector_status)->toBe('success');
});

it('still answers 200 and keeps the order when the provider fails', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response([], 500)]);

    sendOrder(orderPayload())->assertOk();

    expect(Order::count())->toBe(1)
        ->and(Order::sole()->connector_status)->toBe('failure');
});

it('still answers 200 when the job itself throws, and records the failure', function () {
    $this->mock(RunConnectorAction::class, fn ($mock) => $mock->shouldReceive('handle')->andThrow(new RuntimeException('boom')));

    sendOrder(orderPayload())->assertOk();

    $log = IntegrationLog::sole();
    expect(Order::count())->toBe(1)
        ->and($log->operation)->toBe('process_order')
        ->and($log->status->value)->toBe('failure');
});

it('rejects a payload without a valid id and stores nothing', function () {
    Bus::fake();

    sendOrder(['status' => 'processing'])
        ->assertStatus(422)
        ->assertJsonPath('error.detalles.0.campo', 'id');

    expect(Order::count())->toBe(0)->and(WebhookDelivery::count())->toBe(0);
});
