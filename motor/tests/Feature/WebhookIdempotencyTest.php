<?php

use App\Models\IntegrationLog;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['store.webhook_secret' => 'whsec_test']);
    // These tests are about the delivery, not the order processing: the job does not run.
    Bus::fake();
});

function deliver(string $body, ?string $deliveryId = 'delivery-1')
{
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode(hash_hmac('sha256', $body, 'whsec_test', true)),
        'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.created',
    ];
    if ($deliveryId !== null) {
        $server['HTTP_X_WC_WEBHOOK_DELIVERY_ID'] = $deliveryId;
    }

    return test()->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], $server, $body);
}

it('stores the delivery id of a processed delivery', function () {
    deliver('{"id":1001}')->assertOk();

    expect(WebhookDelivery::sole()->delivery_id)->toBe('delivery-1');
});

it('answers 200 to a repeated delivery, stores it once and logs duplicate', function () {
    deliver('{"id":1001}')->assertOk();
    deliver('{"id":1001}')->assertOk();

    expect(WebhookDelivery::count())->toBe(1)
        ->and(IntegrationLog::sole()->status->value)->toBe('duplicate')
        ->and(IntegrationLog::sole()->correlation_id)->toBe('delivery-1');
});

it('answers 200 to the test ping and stores no delivery', function () {
    deliver('{"webhook_id":7}')->assertOk();

    expect(WebhookDelivery::count())->toBe(0)
        ->and(IntegrationLog::sole()->operation)->toBe('webhook_ping');
});

it('rejects a signed delivery without the delivery id header', function () {
    deliver('{"id":1001}', null)
        ->assertStatus(422)
        ->assertJsonPath('error.detalles.0.campo', 'X-WC-Webhook-Delivery-ID');
});
