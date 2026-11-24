<?php

namespace Src\Orders\Application\UseCases;

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\ConnectorResult;
use Carbon\CarbonImmutable;
use Src\IntegrationLog\Application\Contracts\IntegrationLogger;
use Src\IntegrationLog\Domain\Enums\LogDirection;
use Src\IntegrationLog\Domain\Enums\LogStatus;
use Src\IntegrationLog\Domain\Enums\LogSystem;
use Src\Orders\Domain\Contracts\OrderRepository;
use Src\Orders\Domain\Entities\Order;
use Throwable;

/**
 * Runs the connector's action on one order and stores the outcome on the order.
 *
 * It runs inside the job dispatched after the webhook response (#43), so a failure here
 * never changes what the webhook answered: the order stays saved and the failure is recorded.
 */
class RunConnectorAction
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ConnectorInterface $connector,
        private readonly IntegrationLogger $logger,
    ) {}

    /** Receives the id, not the order, so the job can be repeated safely. */
    public function handle(int $orderId): void
    {
        $order = $this->orders->find($orderId);

        // Idempotent: a repeated job must not notify the same order twice.
        if ($order === null || $order->connectorStatus === 'success') {
            return;
        }

        $result = $this->execute($order);

        $this->orders->saveConnectorResult(
            $order->id,
            status: $result->success ? 'success' : 'failure',
            detail: $result->success ? 'sent' : mb_substr((string) $result->detail, 0, 255),
            reference: $result->reference,
            at: CarbonImmutable::now(),
        );
    }

    private function execute(Order $order): ConnectorResult
    {
        try {
            return $this->connector->execute(
                // The store's order number, the one the customer sees; not the engine's own id.
                new ConnectorOrder(
                    orderId: $order->wooId,
                    total: $order->total,
                    currency: $order->currency,
                    lines: $order->lines,
                    orderedAt: $order->orderedAt?->toIso8601String(),
                    status: $order->status,
                    addressText: $order->addressText,
                    address: $order->addressNormalized,
                    addressStatus: $order->addressStatus,
                ),
            );
        } catch (Throwable $e) {
            // The connector should report failures in its result; this is the safety net.
            $this->logger->record(
                direction: LogDirection::Outgoing,
                system: LogSystem::Connector,
                operation: 'run_action',
                status: LogStatus::Failure,
                correlationId: (string) $order->wooId,
                error: $e::class,
            );

            return ConnectorResult::failure('Unexpected connector error');
        }
    }
}
