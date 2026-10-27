<?php

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\MailtrapConnector;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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

function order(): ConnectorOrder
{
    return new ConnectorOrder(1001, '5200.00', 'CRC');
}

it('is bound to the connector chosen in CONECTOR_PROVEEDOR', function () {
    expect(app(ConnectorInterface::class))->toBeInstanceOf(MailtrapConnector::class);
});

it('fails clearly when the provider is unknown', function () {
    config(['connector.provider' => 'nope']);

    expect(fn () => app(ConnectorInterface::class))->toThrow(InvalidArgumentException::class, 'nope');
});

it('sends the email to the sandbox with the bearer token', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['success' => true, 'message_ids' => ['abc-1']])]);

    $result = app(ConnectorInterface::class)->execute(order());

    expect($result->success)->toBeTrue()->and($result->reference)->toBe('abc-1');

    Http::assertSent(fn (Request $r) => $r->url() === 'https://sandbox.api.mailtrap.io/api/send/555'
        && $r->hasHeader('Authorization', 'Bearer mt_secret_token')
        && $r['subject'] === 'Pedido #1001 recibido');
});

it('logs the action without leaking the token', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['message_ids' => ['abc-1']])]);

    app(ConnectorInterface::class)->execute(order());

    $log = IntegrationLog::sole();
    expect($log->system->value)->toBe('connector')
        ->and($log->operation)->toBe('run_action')
        ->and($log->status->value)->toBe('success')
        ->and($log->correlation_id)->toBe('1001')
        ->and(json_encode($log->toArray()))->not->toContain('mt_secret_token');
});

it('reports a provider failure instead of throwing, after three attempts', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response([], 503)]);

    $result = app(ConnectorInterface::class)->execute(order());

    expect($result->success)->toBeFalse()->and($result->detail)->toBe('The provider answered 503');
    Http::assertSentCount(3);

    $log = IntegrationLog::sole();
    expect($log->status->value)->toBe('failure')
        ->and($log->attempts)->toBe(3)
        ->and(json_encode($log->toArray()))->not->toContain('mt_secret_token');
});

it('does not retry an invalid token', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::response(['errors' => ['Unauthorized']], 401)]);

    $result = app(ConnectorInterface::class)->execute(order());

    expect($result->success)->toBeFalse();
    Http::assertSentCount(1);
});

it('reports a connection error as a failure', function () {
    Http::fake(['sandbox.api.mailtrap.io/*' => Http::sequence()->pushFailedConnection()->pushFailedConnection()->pushFailedConnection()]);

    expect(app(ConnectorInterface::class)->execute(order())->detail)->toBe('No connection to the provider');
});
