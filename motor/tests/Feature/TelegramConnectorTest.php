<?php

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\TelegramConnector;
use Src\IntegrationLog\Infrastructure\Persistence\Models\IntegrationLog;
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
        && str_contains($r['text'], 'Pedido: #1001')
        && str_contains($r['text'], 'Total: ₡10.800,00'));
});

it('sends a full invoice with the products, shipping charge and normalized location', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 43]])]);

    app(ConnectorInterface::class)->execute(new ConnectorOrder(
        orderId: 140,
        total: '12400',
        currency: 'CRC',
        lines: [['sku' => 'LIB-00029', 'name' => 'La isla de los hombres solos', 'quantity' => 1, 'unit_price' => 9900, 'subtotal' => 9900]],
        orderedAt: '2026-11-16T22:58:08-06:00',
        status: 'on-hold',
        addressText: 'Iglesia de Pital, Pital, San Carlos',
        address: ['provincia' => 'Alajuela', 'canton' => 'San Carlos', 'distrito' => 'Pital', 'otras_senas' => '200 m sur de la iglesia', 'confianza' => 0.92],
        addressStatus: 'normalized',
    ));

    Http::assertSent(function (Request $r) {
        $text = $r['text'];

        return str_contains($text, '1 x La isla de los hombres solos (LIB-00029)')
            && str_contains($text, 'Subtotal: ₡9.900,00')
            && str_contains($text, 'Envío y otros cargos: ₡2.500,00')
            && str_contains($text, 'Total: ₡12.400,00')
            && str_contains($text, 'Provincia: Alajuela')
            && str_contains($text, 'Distrito: Pital')
            && str_contains($text, 'Otras señas: 200 m sur de la iglesia');
    });
});

it('shows the raw address and the review state when the AI could not normalize it', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 44]])]);

    app(ConnectorInterface::class)->execute(new ConnectorOrder(
        orderId: 141, total: '9900', currency: 'CRC',
        addressText: 'PITAL, Pital, CR-A', addressStatus: 'pending_review',
    ));

    Http::assertSent(fn (Request $r) => str_contains($r['text'], 'Dirección: PITAL, Pital, CR-A')
        && str_contains($r['text'], 'pendiente de revisión'));
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
