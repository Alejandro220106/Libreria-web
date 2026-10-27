<?php

use App\Application\Ports\AddressNormalizer;
use App\Domain\Address\InvalidAddressResponse;
use App\Infrastructure\Ai\OllamaAddressNormalizer;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const VALID_REPLY = [
    'provincia' => 'Alajuela',
    'canton' => 'San Carlos',
    'distrito' => 'Quesada',
    'otras_senas' => '100 metros norte de la iglesia, casa verde',
    'confianza' => 0.93,
];

function ollamaReplies(array $content, int $status = 200): void
{
    Http::fake([
        '*/api/chat' => Http::response(['message' => ['content' => json_encode($content)]], $status),
    ]);
}

it('is bound to the Ollama adapter', function () {
    expect(app(AddressNormalizer::class))->toBeInstanceOf(OllamaAddressNormalizer::class);
});

it('returns the structured address and sends the schema to Ollama', function () {
    ollamaReplies(VALID_REPLY);

    $result = app(AddressNormalizer::class)->normalize('100 metros norte de la iglesia de Ciudad Quesada, casa verde', '9001');

    expect($result)->toBe(VALID_REPLY);

    Http::assertSent(fn ($request) => $request['model'] === 'qwen2.5:7b'
        && $request['stream'] === false
        && $request['format']['title'] === 'DireccionNormalizada'
        && $request['messages'][1]['content'] === '100 metros norte de la iglesia de Ciudad Quesada, casa verde');
});

it('rejects a province outside the allowed list', function () {
    ollamaReplies([...VALID_REPLY, 'provincia' => 'Narnia']);

    app(AddressNormalizer::class)->normalize('cualquier dirección');
})->throws(InvalidAddressResponse::class);

it('rejects a reply with an extra field', function () {
    ollamaReplies([...VALID_REPLY, 'telefono' => '8888-8888']);

    app(AddressNormalizer::class)->normalize('cualquier dirección');
})->throws(InvalidAddressResponse::class);

it('rejects a confidence above 1', function () {
    ollamaReplies([...VALID_REPLY, 'confianza' => 1.5]);

    app(AddressNormalizer::class)->normalize('cualquier dirección');
})->throws(InvalidAddressResponse::class);

it('rejects a reply that is not a JSON object', function () {
    Http::fake(['*/api/chat' => Http::response(['message' => ['content' => 'lo siento, no puedo']])]);

    app(AddressNormalizer::class)->normalize('cualquier dirección');
})->throws(InvalidAddressResponse::class);

it('fails when Ollama answers with a server error', function () {
    Http::fake(['*/api/chat' => Http::response('boom', 500)]);

    app(AddressNormalizer::class)->normalize('cualquier dirección');
})->throws(RequestException::class);

it('records a successful call without the address', function () {
    ollamaReplies(VALID_REPLY);

    app(AddressNormalizer::class)->normalize('dirección privada de prueba', '9001');

    $log = IntegrationLog::sole();
    expect($log->operation)->toBe('normalize_address')
        ->and($log->system->value)->toBe('ai')
        ->and($log->status->value)->toBe('success')
        ->and($log->http_code)->toBe(200)
        ->and($log->correlation_id)->toBe('9001')
        ->and(json_encode($log->toArray()))->not->toContain('privada');
});

it('records an invalid reply as a failure and keeps the error class only', function () {
    ollamaReplies([...VALID_REPLY, 'provincia' => 'Narnia']);

    try {
        app(AddressNormalizer::class)->normalize('dirección privada de prueba', '9002');
    } catch (InvalidAddressResponse) {
    }

    $log = IntegrationLog::sole();
    expect($log->status->value)->toBe('failure')
        ->and($log->error)->toBe(InvalidAddressResponse::class)
        ->and($log->correlation_id)->toBe('9002');
});
