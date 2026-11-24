<?php

namespace Src\Orders\Infrastructure\Webhooks;

use Src\Webhooks\Application\Contracts\WebhookHandler;
use Src\Orders\Application\UseCases\StoreOrder;
use Src\Orders\Infrastructure\Jobs\ProcessOrder;
use Carbon\CarbonImmutable;

/**
 * Topic order.updated: refresh the engine's copy of the order.
 *
 * StoreOrder resets the address when it changed, so the job runs again to normalize it.
 * The job skips steps already done, so the connector action is not repeated.
 */
class OrderUpdatedHandler implements WebhookHandler
{
    public function __construct(private readonly StoreOrder $storeOrder) {}

    public function operation(): string
    {
        return 'order_updated_webhook';
    }

    public function handle(array $payload): void
    {
        $order = $this->storeOrder->handle($payload, CarbonImmutable::now());

        ProcessOrder::dispatchAfterResponse($order->id);
    }
}
