<?php

namespace App\Application\Orders;

use App\Application\Ports\IntegrationLogger;
use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\ConnectorResult;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Models\Order;
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
        private readonly ConnectorInterface $connector,
        private readonly IntegrationLogger $logger,
    ) {}

    /** Receives the id, not the model, so the job can be repeated safely. */
    public function handle(int $orderId): void
    {
        $order = Order::find($orderId);

        // Idempotent: a repeated job must not notify the same order twice.
        if ($order === null || $order->connector_status === 'success') {
            return;
        }

        $result = $this->execute($order);

        $order->update([
            'connector_status' => $result->success ? 'success' : 'failure',
            'connector_detail' => $result->success ? 'sent' : mb_substr((string) $result->detail, 0, 255),
            'connector_reference' => $result->reference,
            'connector_updated_at' => now()->utc(),
        ]);
    }

    private function execute(Order $order): ConnectorResult
    {
        try {
            return $this->connector->execute(
                new ConnectorOrder($order->id, (string) $order->total, $order->currency),
            );
        } catch (Throwable $e) {
            // The connector should report failures in its result; this is the safety net.
            $this->logger->record(
                direction: LogDirection::Outgoing,
                system: LogSystem::Connector,
                operation: 'run_action',
                status: LogStatus::Failure,
                correlationId: (string) $order->id,
                error: $e::class,
            );

            return ConnectorResult::failure('Unexpected connector error');
        }
    }
}
