<?php

namespace App\Application\Orders;

use App\Application\Ports\AddressNormalizer;
use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Models\Order;
use Throwable;

/**
 * Normalizes an order's shipping address with the AI model (#49, #50).
 *
 * Only a valid reply with enough confidence is saved. An invalid reply, low confidence
 * or a provider failure leaves the address pending review, so no order ships to a
 * wrong address, and nothing is thrown so the engine keeps processing other orders.
 */
class NormalizeOrderAddress
{
    public function __construct(
        private readonly AddressNormalizer $normalizer,
        private readonly IntegrationLogger $logger,
    ) {}

    public function handle(int $orderId): void
    {
        $order = Order::find($orderId);

        // Idempotent: an address already normalized or flagged is not sent again.
        if ($order === null || $order->address_status !== 'unprocessed') {
            return;
        }

        try {
            // The adapter validates the reply against the schema and logs the call.
            $address = $this->normalizer->normalize($order->address_text, (string) $order->woo_id);
        } catch (Throwable) {
            $this->markPendingReview($order);

            return;
        }

        if ($address['confianza'] < (float) config('ai.min_confidence')) {
            $this->logger->record(
                direction: LogDirection::Outgoing,
                system: LogSystem::Ai,
                operation: 'normalize_address',
                status: LogStatus::Failure,
                correlationId: (string) $order->woo_id,
                error: 'Low confidence',
            );
            $this->markPendingReview($order);

            return;
        }

        $order->update(['address_status' => 'normalized', 'address_normalized' => $address]);
    }

    private function markPendingReview(Order $order): void
    {
        $order->update(['address_status' => 'pending_review', 'address_normalized' => null]);
    }
}
