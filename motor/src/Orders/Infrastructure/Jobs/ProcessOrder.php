<?php

namespace Src\Orders\Infrastructure\Jobs;

use Src\Orders\Application\UseCases\NormalizeOrderAddress;
use Src\Orders\Application\UseCases\RunConnectorAction;
use Src\IntegrationLog\Application\Contracts\IntegrationLogger;
use Src\IntegrationLog\Domain\Enums\LogDirection;
use Src\IntegrationLog\Domain\Enums\LogStatus;
use Src\IntegrationLog\Domain\Enums\LogSystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

/**
 * The slow work of a new order: the connector action (#46) and the address
 * normalization (#49, #50).
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

    public function handle(RunConnectorAction $connector, NormalizeOrderAddress $address, IntegrationLogger $logger): void
    {
        // Whatever happens here, the webhook already answered 200. Each step has its own
        // try/catch, so a failure in one does not stop the other. Failures are recorded, never thrown.
        // The address goes first, so the connector's message can include the normalized location.
        $this->attempt(fn () => $address->handle($this->orderId), $logger);
        $this->attempt(fn () => $connector->handle($this->orderId), $logger);
    }

    private function attempt(callable $step, IntegrationLogger $logger): void
    {
        try {
            $step();
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
    }
}
