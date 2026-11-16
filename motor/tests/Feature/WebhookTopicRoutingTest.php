<?php

use App\Jobs\ProcessOrder;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Bus;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

beforeEach(function () {
    config(['store.webhook_secret' => 'whsec_test']);
    Bus::fake();
});

function deliverTopic(string $topic, array $payload, string $deliveryId)
{
    $body = json_encode($payload);

    return test()->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode(hash_hmac('sha256', $body, 'whsec_test', true)),
        'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => $deliveryId,
        'HTTP_X_WC_WEBHOOK_TOPIC' => $topic,
    ], $body);
}

it('answers 200 to a topic with no handler, logs it and saves nothing', function () {
    deliverTopic('product.deleted', ['id' => 55], 'delivery-x')->assertOk();

    $log = IntegrationLog::sole();

    expect(Order::count())->toBe(0)
        ->and(WebhookDelivery::count())->toBe(0)
        ->and($log->operation)->toBe('webhook_unhandled_topic')
        ->and($log->error)->toContain('product.deleted');

    Bus::assertNothingDispatched();
});

it('updates the saved order on order.updated and processes it again', function () {
    deliverTopic('order.created', ['id' => 3001, 'status' => 'processing'], 'delivery-1')->assertOk();
    deliverTopic('order.updated', ['id' => 3001, 'status' => 'completed'], 'delivery-2')->assertOk();

    expect(Order::sole()->status)->toBe('completed')
        ->and(WebhookDelivery::pluck('topic')->sort()->values()->all())->toBe(['order.created', 'order.updated']);

    Bus::assertDispatchedAfterResponseTimes(ProcessOrder::class, 2);
});

it('logs a duplicate with the operation of its topic', function () {
    deliverTopic('order.updated', ['id' => 3001], 'delivery-1')->assertOk();
    deliverTopic('order.updated', ['id' => 3001], 'delivery-1')->assertOk();

    expect(IntegrationLog::sole()->operation)->toBe('order_updated_webhook');
});
