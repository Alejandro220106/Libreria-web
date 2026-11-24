<?php

namespace Src\Orders\Infrastructure\Webhooks;

use Src\Webhooks\Application\Contracts\WebhookHandler;
use Src\Orders\Application\UseCases\StoreOrder;
use Src\Orders\Infrastructure\Jobs\ProcessOrder;
use Carbon\CarbonImmutable;

/** Topic order.created: save the order and process it after the response (brief 6.5). */
class OrderCreatedHandler implements WebhookHandler
{
    public function __construct(private readonly StoreOrder $storeOrder) {}

    public function operation(): string
    {
        return 'order_created_webhook';
    }

    public function handle(array $payload): void
    {
        $order = $this->storeOrder->handle($payload, CarbonImmutable::now());

        // The connector and the AI are slow and can fail: they run after the response is sent,
        // so WooCommerce gets its 200 at once and a failure there never changes it.
        ProcessOrder::dispatchAfterResponse($order->id);
    }
}
