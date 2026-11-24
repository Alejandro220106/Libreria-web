<?php

use Src\Orders\Application\UseCases\NormalizeOrderAddress;
use Src\IntegrationLog\Infrastructure\Persistence\Models\IntegrationLog;
use Src\Orders\Infrastructure\Persistence\Models\Order;
use Illuminate\Support\Facades\Http;
use Tests\Support\RefreshDatabaseAsOwner;

uses(RefreshDatabaseAsOwner::class);

function aiReply(float $confidence, array $overrides = []): array
{
    return array_merge([
        'provincia' => 'Alajuela',
        'canton' => 'San Carlos',
        'distrito' => 'Quesada',
        'otras_senas' => '100 metros norte de la iglesia, casa verde',
        'confianza' => $confidence,
    ], $overrides);
}

function fakeAi(array $content, int $status = 200): void
{
    Http::fake(['*/api/chat' => Http::response(['message' => ['content' => json_encode($content)]], $status)]);
}

function unprocessedOrder(int $wooId = 3001): Order
{
    return Order::create([
        'woo_id' => $wooId,
        'status' => 'processing',
        'currency' => 'CRC',
        'total' => '16900.00',
        'lines' => [],
        'address_text' => '100 metros norte de la iglesia, Ciudad Quesada',
        'address_status' => 'unprocessed',
        'ordered_at' => now(),
        'synced_at' => now(),
    ]);
}

it('saves the address when the reply is valid and confident enough', function () {
    fakeAi(aiReply(0.93));
    $order = unprocessedOrder();

    app(NormalizeOrderAddress::class)->handle($order->id);

    expect($order->fresh()->address_status)->toBe('normalized')
        ->and($order->fresh()->address_normalized['canton'])->toBe('San Carlos');
});

it('accepts a confidence exactly at the threshold', function () {
    fakeAi(aiReply(0.85));
    $order = unprocessedOrder();

    app(NormalizeOrderAddress::class)->handle($order->id);

    expect($order->fresh()->address_status)->toBe('normalized');
});

it('leaves the address pending review and logs it when confidence is below 0.85', function () {
    fakeAi(aiReply(0.84));
    $order = unprocessedOrder();

    app(NormalizeOrderAddress::class)->handle($order->id);

    expect($order->fresh()->address_status)->toBe('pending_review')
        ->and($order->fresh()->address_normalized)->toBeNull()
        ->and(IntegrationLog::where('error', 'Low confidence')->count())->toBe(1);
});

it('leaves the address pending review when the reply does not match the schema', function () {
    fakeAi(aiReply(0.95, ['provincia' => 'Narnia']));
    $order = unprocessedOrder();

    app(NormalizeOrderAddress::class)->handle($order->id);

    expect($order->fresh()->address_status)->toBe('pending_review')
        ->and(IntegrationLog::sole()->status->value)->toBe('failure');
});

it('leaves the address pending review when the provider fails', function () {
    fakeAi([], 500);
    $order = unprocessedOrder();

    app(NormalizeOrderAddress::class)->handle($order->id);

    expect($order->fresh()->address_status)->toBe('pending_review')
        ->and(IntegrationLog::sole()->http_code)->toBe(500);
});

it('keeps processing the next order after one fails', function () {
    Http::fakeSequence('*/api/chat')
        ->push([], 500)
        ->push(['message' => ['content' => json_encode(aiReply(0.9))]]);
    $first = unprocessedOrder(3001);
    $second = unprocessedOrder(3002);

    app(NormalizeOrderAddress::class)->handle($first->id);
    app(NormalizeOrderAddress::class)->handle($second->id);

    expect($first->fresh()->address_status)->toBe('pending_review')
        ->and($second->fresh()->address_status)->toBe('normalized');
});

it('does not call the model again for an address already processed', function () {
    fakeAi(aiReply(0.9));
    $order = unprocessedOrder();
    $order->update(['address_status' => 'pending_review']);

    app(NormalizeOrderAddress::class)->handle($order->id);

    Http::assertNothingSent();
});

it('reads the threshold from the configuration', function () {
    config(['ai.min_confidence' => 0.95]);
    fakeAi(aiReply(0.9));
    $order = unprocessedOrder();

    app(NormalizeOrderAddress::class)->handle($order->id);

    expect($order->fresh()->address_status)->toBe('pending_review');
});
