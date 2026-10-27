<?php

use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => config(['store.webhook_secret' => 'whsec_test']));

function signed(string $body, string $secret = 'whsec_test'): array
{
    return [
        'X-WC-Webhook-Signature' => base64_encode(hash_hmac('sha256', $body, $secret, true)),
        'X-WC-Webhook-Delivery-ID' => 'delivery-1',
        'Content-Type' => 'application/json',
    ];
}

it('accepts a delivery signed with the webhook secret', function () {
    $body = '{"id": 1001,  "status":"processing"}';

    $this->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], headersToServer(signed($body)), $body)
        ->assertOk();

    expect(IntegrationLog::count())->toBe(0);
});

it('rejects a wrong signature with 401 and logs invalid_signature', function () {
    $body = '{"id":1001}';

    $this->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], headersToServer(signed($body, 'other_secret')), $body)
        ->assertStatus(401)
        ->assertJsonPath('error.codigo', 'firma_invalida');

    $log = IntegrationLog::sole();
    expect($log->status->value)->toBe('invalid_signature')
        ->and($log->http_code)->toBe(401)
        ->and($log->correlation_id)->toBe('delivery-1')
        ->and(json_encode($log->toArray()))->not->toContain('whsec_test');
});

it('rejects a delivery without the signature header', function () {
    $this->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], [], '{"id":1001}')
        ->assertStatus(401);

    expect(IntegrationLog::sole()->status->value)->toBe('invalid_signature');
});

it('rejects everything when no secret is configured', function () {
    config(['store.webhook_secret' => '']);
    $body = '{"id":1001}';

    $this->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], headersToServer(signed($body, '')), $body)
        ->assertStatus(401);
});

function headersToServer(array $headers): array
{
    $server = [];
    foreach ($headers as $name => $value) {
        $key = strtoupper(str_replace('-', '_', $name));
        $server[in_array($key, ['CONTENT_TYPE'], true) ? $key : 'HTTP_'.$key] = $value;
    }

    return $server;
}
