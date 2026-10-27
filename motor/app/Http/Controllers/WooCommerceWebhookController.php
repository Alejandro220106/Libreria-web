<?php

namespace App\Http\Controllers;

use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Domain\Webhook\SignatureVerifier;
use App\Http\Responses\ApiError;
use App\Models\WebhookDelivery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class WooCommerceWebhookController extends Controller
{
    public function __invoke(Request $request, SignatureVerifier $verifier, IntegrationLogger $logger)
    {
        // The raw body, never the re-encoded JSON: the signature was computed over the exact bytes.
        $isValid = $verifier->isValid(
            $request->getContent(),
            $request->header('X-WC-Webhook-Signature'),
            config('store.webhook_secret'),
        );

        if (! $isValid) {
            $this->log($logger, 'order_created_webhook', LogStatus::InvalidSignature, 401, $request, 'The signature does not match');

            return ApiError::response(401, 'firma_invalida', 'La firma del webhook no es válida.');
        }

        // WooCommerce sends a ping when a webhook is saved: its body is only webhook_id.
        if (array_keys($request->all()) === ['webhook_id']) {
            $this->log($logger, 'webhook_ping', LogStatus::Success, 200, $request);

            return response()->json([]);
        }

        $deliveryId = $this->deliveryId($request);

        if ($deliveryId === null) {
            return ApiError::response(422, 'validacion', 'Falta la cabecera de la entrega.', [
                ['campo' => 'X-WC-Webhook-Delivery-ID', 'mensaje' => 'La cabecera es obligatoria.'],
            ]);
        }

        try {
            // The unique index on delivery_id decides, so two concurrent retries cannot both pass.
            WebhookDelivery::create([
                'delivery_id' => $deliveryId,
                'topic' => mb_substr((string) $request->header('X-WC-Webhook-Topic', 'unknown'), 0, 60),
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->log($logger, 'order_created_webhook', LogStatus::Duplicate, 200, $request);

            return response()->json([]);
        }

        // Processing the order comes in #43; answering 200 first keeps WooCommerce from retrying.
        return response()->json([]);
    }

    private function deliveryId(Request $request): ?string
    {
        // Controlled by the sender, so it is cut to the column size.
        return mb_substr((string) $request->header('X-WC-Webhook-Delivery-ID'), 0, 100) ?: null;
    }

    private function log(IntegrationLogger $logger, string $operation, LogStatus $status, int $httpCode, Request $request, ?string $error = null): void
    {
        $logger->record(
            direction: LogDirection::Incoming,
            system: LogSystem::Store,
            operation: $operation,
            status: $status,
            httpCode: $httpCode,
            correlationId: $this->deliveryId($request),
            error: $error,
        );
    }
}
