<?php

use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Infrastructure\Logging\DatabaseIntegrationLogger;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('is bound to the database adapter', function () {
    expect(app(IntegrationLogger::class))->toBeInstanceOf(DatabaseIntegrationLogger::class);
});

it('stores one row with every field of the contract', function () {
    app(IntegrationLogger::class)->record(
        LogDirection::Incoming,
        LogSystem::Store,
        'order_created_webhook',
        LogStatus::InvalidSignature,
        httpCode: 401,
        durationMs: 4,
        correlationId: 'delivery-1',
        error: 'The signature does not match',
    );

    $log = IntegrationLog::sole();
    expect($log->direction)->toBe(LogDirection::Incoming)
        ->and($log->system)->toBe(LogSystem::Store)
        ->and($log->operation)->toBe('order_created_webhook')
        ->and($log->status)->toBe(LogStatus::InvalidSignature)
        ->and($log->http_code)->toBe(401)
        ->and($log->attempts)->toBe(1)
        ->and($log->duration_ms)->toBe(4)
        ->and($log->correlation_id)->toBe('delivery-1')
        ->and($log->logged_at)->not->toBeNull();
});

it('cuts long error messages so no body can slip in', function () {
    app(IntegrationLogger::class)->record(
        LogDirection::Outgoing,
        LogSystem::Connector,
        'run_action',
        LogStatus::Failure,
        error: str_repeat('x', 2000),
    );

    expect(mb_strlen(IntegrationLog::sole()->error))->toBe(500);
});
