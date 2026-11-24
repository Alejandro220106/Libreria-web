<?php

namespace Src\Orders\Application\UseCases;

use Src\IntegrationLog\Application\Contracts\IntegrationLogger;
use Src\IntegrationLog\Domain\Enums\LogDirection;
use Src\IntegrationLog\Domain\Enums\LogStatus;
use Src\IntegrationLog\Domain\Enums\LogSystem;
use Src\Orders\Application\Contracts\AddressNormalizer;
use Src\Orders\Domain\Contracts\OrderRepository;
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
        private readonly OrderRepository $orders,
        private readonly AddressNormalizer $normalizer,
        private readonly IntegrationLogger $logger,
    ) {}

    public function handle(int $orderId): void
    {
        $order = $this->orders->find($orderId);

        // Idempotent: an address already normalized or flagged is not sent again.
        if ($order === null || $order->addressStatus !== 'unprocessed') {
            return;
        }

        try {
            // The adapter validates the reply against the schema and logs the call.
            $address = $this->normalizer->normalize($order->addressText, (string) $order->wooId);
        } catch (Throwable) {
            $this->orders->markAddressPendingReview($order->id);

            return;
        }

        if ($address['confianza'] < (float) config('ai.min_confidence')) {
            $this->logger->record(
                direction: LogDirection::Outgoing,
                system: LogSystem::Ai,
                operation: 'normalize_address',
                status: LogStatus::Failure,
                correlationId: (string) $order->wooId,
                error: 'Low confidence',
            );
            $this->orders->markAddressPendingReview($order->id);

            return;
        }

        $this->orders->markAddressNormalized($order->id, $address);
    }
}
