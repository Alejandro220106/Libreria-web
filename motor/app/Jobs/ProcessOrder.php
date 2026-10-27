<?php

namespace App\Jobs;

use App\Application\Orders\RunConnectorAction;
use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

/**
 * The slow work of a new order: the connector action (#46) and, when #49 lands,
 * the address normalization.
 *
 * Today it runs right after the webhook response (dispatchAfterResponse). It implements
 * ShouldQueue and receives only an id, so moving it to a queue worker later means
 * changing the dispatch call, not this class. It is safe to repeat: each step skips
 * work that is already done.
 */
class ProcessOrder implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public readonly int $orderId) {}

    public function handle(RunConnectorAction $connector, IntegrationLogger $logger): void
    {
        // Whatever happens here, the webhook already answered 200. It is recorded, never thrown.
        try {
            $connector->handle($this->orderId);
        } catch (Throwable $e) {
            $logger->record(
                direction: LogDirection::Incoming,
                system: LogSystem::Store,
                operation: 'process_order',
                status: LogStatus::Failure,
                correlationId: (string) $this->orderId,
                error: $e::class,
            );
        }

        // #49 plugs the address normalization in here, in its own try/catch so that
        // a failure in one step does not stop the other.
    }
}
