<?php

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\TelegramConnector;
use App\Models\IntegrationLog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

beforeEach(function () {
    Http::preventStrayRequests();

    config([
        'connector.provider' => 'telegram',
        'connector.backoff_ms' => 0,
        'connector.telegram.url' => 'https://api.telegram.org',
        'connector.telegram.token' => '123:tg_secret_token',
        'connector.telegram.chat_id' => '-100555',
    ]);
});

function telegramOrder(): ConnectorOrder
{
    return new ConnectorOrder(1001, '10800', 'CRC');
}

it('is bound when CONECTOR_PROVEEDOR is telegram', function () {
    expect(app(ConnectorInterface::class))->toBeInstanceOf(TelegramConnector::class);
});

it('sends the order message to the test chat', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);

    $result = app(ConnectorInterface::class)->execute(telegramOrder());

    expect($result->success)->toBeTrue()->and($result->reference)->toBe('42');

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.telegram.org/bot123:tg_secret_token/sendMessage'
        && $r['chat_id'] === '-100555'
        && $r['text'] === "Gracias por su compra.\nPedido: #1001\nTotal: 10800.00 CRC");
});

it('retries a 5xx three times and reports the failure without throwing', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 502)]);

    $result = app(ConnectorInterface::class)->execute(telegramOrder());

    expect($result->success)->toBeFalse()->and($result->detail)->toBe('The provider answered 502');
    Http::assertSentCount(3);

    $log = IntegrationLog::sole();
    expect($log->status->value)->toBe('failure')->and($log->attempts)->toBe(3);
});

it('does not retry a 401 from a wrong token', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401)]);

    $result = app(ConnectorInterface::class)->execute(telegramOrder());

    expect($result->success)->toBeFalse();
    Http::assertSentCount(1);
});

it('never writes the bot token to the log', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);

    app(ConnectorInterface::class)->execute(telegramOrder());

    expect(json_encode(IntegrationLog::sole()->toArray()))->not->toContain('tg_secret_token');
});
