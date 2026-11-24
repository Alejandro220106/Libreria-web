<?php

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\MailtrapConnector;
use App\Conectores\TelegramConnector;
use Src\IntegrationLog\Infrastructure\Persistence\Models\IntegrationLog;
use Illuminate\Support\Facades\Http;
use Tests\Support\RefreshDatabaseAsOwner;

/*
| Contract every connector must honour (ConnectorInterface): run the action for a
| new order, report a provider failure instead of throwing, and never write a
| secret to the integration log. The same checks run against each connector.
|
| Adding a connector means adding one entry to contractConnectors() below;
| the last test fails until that is done.
*/

uses(RefreshDatabaseAsOwner::class);

/** Connectors under test, keyed by their name in config/connector.php. */
function contractConnectors(): array
{
    return [
        'telegram' => [
            'name' => 'telegram',
            'class' => TelegramConnector::class,
            'config' => [
                'connector.telegram.url' => 'https://api.telegram.org',
                'connector.telegram.token' => '123:contract_secret_token',
                'connector.telegram.chat_id' => '-100555',
            ],
            'host' => 'api.telegram.org/*',
            'success' => ['ok' => true, 'result' => ['message_id' => 42]],
            'secret' => 'contract_secret_token',
        ],
        'mailtrap' => [
            'name' => 'mailtrap',
            'class' => MailtrapConnector::class,
            'config' => [
                'connector.mailtrap.url' => 'https://sandbox.api.mailtrap.io/api/send',
                'connector.mailtrap.token' => 'contract_secret_token',
                'connector.mailtrap.inbox_id' => '555',
            ],
            'host' => 'sandbox.api.mailtrap.io/*',
            'success' => ['message_ids' => ['abc-1']],
            'secret' => 'contract_secret_token',
        ],
    ];
}

// One dataset case per connector; each case receives the whole entry as $connector.
dataset('connectors', array_map(fn (array $connector) => [$connector], contractConnectors()));

/** Selects the connector under test, as CONECTOR_PROVEEDOR would, and returns it. */
function contractConnector(array $connector): ConnectorInterface
{
    config(['connector.provider' => $connector['name'], 'connector.backoff_ms' => 0] + $connector['config']);

    return app(ConnectorInterface::class);
}

function contractOrder(): ConnectorOrder
{
    return new ConnectorOrder(3001, '7500.00', 'CRC');
}

it('is bound when CONECTOR_PROVEEDOR names it', function (array $connector) {
    expect(contractConnector($connector))->toBeInstanceOf($connector['class']);
})->with('connectors');

it('runs the action for a new order and logs it', function (array $connector) {
    Http::fake([$connector['host'] => Http::response($connector['success'])]);

    $result = contractConnector($connector)->execute(contractOrder());

    expect($result->success)->toBeTrue();
    Http::assertSentCount(1);

    $log = IntegrationLog::sole();
    expect($log->system->value)->toBe('connector')
        ->and($log->operation)->toBe('run_action')
        ->and($log->status->value)->toBe('success')
        ->and($log->correlation_id)->toBe('3001');
})->with('connectors');

it('reports a provider error as a failure after the retries, without throwing', function (array $connector) {
    Http::fake([$connector['host'] => Http::response([], 503)]);

    $result = contractConnector($connector)->execute(contractOrder());

    expect($result->success)->toBeFalse()
        ->and($result->detail)->toBe('The provider answered 503');
    Http::assertSentCount(config('connector.tries'));

    $log = IntegrationLog::sole();
    expect($log->status->value)->toBe('failure')
        ->and($log->attempts)->toBe(config('connector.tries'));
})->with('connectors');

it('reports a connection error as a failure, without throwing', function (array $connector) {
    Http::fake([$connector['host'] => Http::failedConnection()]);

    $result = contractConnector($connector)->execute(contractOrder());

    expect($result->success)->toBeFalse()
        ->and($result->detail)->toBe('No connection to the provider');
    expect(IntegrationLog::sole()->status->value)->toBe('failure');
})->with('connectors');

it('never writes the secret to the integration log', function (array $connector) {
    Http::fake([$connector['host'] => Http::sequence()
        ->push($connector['success'])
        ->push([], 503)->push([], 503)->push([], 503)]);

    $instance = contractConnector($connector);
    $instance->execute(contractOrder());
    $instance->execute(contractOrder());

    expect(IntegrationLog::count())->toBe(2)
        ->and(json_encode(IntegrationLog::all()->toArray()))->not->toContain($connector['secret']);
})->with('connectors');

it('covers every registered connector', function () {
    $tested = array_keys(contractConnectors());
    $registered = array_keys(config('connector.providers'));
    sort($tested);
    sort($registered);

    expect($tested)->toBe($registered);
});
