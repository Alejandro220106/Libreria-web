<?php

namespace App\Http\Controllers;

use App\Application\Orders\StoreOrder;
use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Domain\Webhook\SignatureVerifier;
use App\Http\Responses\ApiError;
use App\Jobs\ProcessOrder;
use App\Models\WebhookDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WooCommerceWebhookController extends Controller
{
    public function __invoke(Request $request, SignatureVerifier $verifier, IntegrationLogger $logger, StoreOrder $storeOrder)
    {
        // WooCommerce sends this ping when a webhook is saved and does not sign it. It carries
        // no order data and changes nothing, so it is answered before the signature check.
        if (preg_match('/\Awebhook_id=\d+\z/', $request->getContent()) === 1) {
            $this->log($logger, 'webhook_ping', LogStatus::Success, 200, $request);

            return response()->json([]);
        }

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

        $payload = $request->json()->all();

        if (! is_int($payload['id'] ?? null) || $payload['id'] < 1) {
            return ApiError::response(422, 'validacion', 'El pedido no trae un id válido.', [
                ['campo' => 'id', 'mensaje' => 'Debe ser un entero positivo.'],
            ]);
        }

        try {
            // One transaction: the delivery is marked processed only if the order was saved.
            // Otherwise a failure would leave the delivery recorded and WooCommerce's retry
            // would be taken for a duplicate, losing the order.
            $order = DB::transaction(function () use ($deliveryId, $request, $payload, $storeOrder) {
                // The unique index on delivery_id decides, so two concurrent retries cannot both pass.
                WebhookDelivery::create([
                    'delivery_id' => $deliveryId,
                    'topic' => mb_substr((string) $request->header('X-WC-Webhook-Topic', 'unknown'), 0, 60),
                ]);

                return $storeOrder->handle($payload, CarbonImmutable::now('UTC'));
            });
        } catch (UniqueConstraintViolationException) {
            $this->log($logger, 'order_created_webhook', LogStatus::Duplicate, 200, $request);

            return response()->json([]);
        }

        // The connector and the AI are slow and can fail: they run after the response is sent,
        // so WooCommerce gets its 200 at once and a failure there never changes it.
        ProcessOrder::dispatchAfterResponse($order->id);

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
