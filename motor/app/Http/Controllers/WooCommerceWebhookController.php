<?php

namespace App\Http\Controllers;

use App\Application\Ports\IntegrationLogger;
use App\Application\Webhooks\WebhookHandler;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Domain\Webhook\SignatureVerifier;
use App\Http\Responses\ApiError;
use App\Models\WebhookDelivery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WooCommerceWebhookController extends Controller
{
    public function __invoke(Request $request, SignatureVerifier $verifier, IntegrationLogger $logger)
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
            $this->log($logger, 'webhook', LogStatus::InvalidSignature, 401, $request, 'The signature does not match');

            return ApiError::response(401, 'firma_invalida', __('api.errors.invalid_signature'));
        }

        // WooCommerce sends a ping when a webhook is saved: its body is only webhook_id.
        if (array_keys($request->all()) === ['webhook_id']) {
            $this->log($logger, 'webhook_ping', LogStatus::Success, 200, $request);

            return response()->json([]);
        }

        $deliveryId = $this->deliveryId($request);

        if ($deliveryId === null) {
            return ApiError::response(422, 'validacion', __('api.errors.missing_delivery_header'), [
                ['campo' => 'X-WC-Webhook-Delivery-ID', 'mensaje' => __('api.fields.header_required')],
            ]);
        }

        $payload = $request->json()->all();

        if (! is_int($payload['id'] ?? null) || $payload['id'] < 1) {
            return ApiError::response(422, 'validacion', __('api.errors.invalid_order_id'), [
                ['campo' => 'id', 'mensaje' => __('api.fields.positive_integer')],
            ]);
        }

        $topic = (string) $request->header('X-WC-Webhook-Topic', '');
        $handler = $this->handlerFor($topic);

        if ($handler === null) {
            // A 200 keeps WooCommerce from pausing the webhook; the log shows what arrived.
            $this->log($logger, 'webhook_unhandled_topic', LogStatus::Success, 200, $request, "No handler for topic [{$topic}]");

            return response()->json([]);
        }

        try {
            // One transaction: the delivery is marked processed only if the handler succeeded.
            // Otherwise a failure would leave the delivery recorded and WooCommerce's retry
            // would be taken for a duplicate, losing the order.
            DB::transaction(function () use ($deliveryId, $topic, $payload, $handler) {
                // The unique index on delivery_id decides, so two concurrent retries cannot both pass.
                WebhookDelivery::create([
                    'delivery_id' => $deliveryId,
                    'topic' => mb_substr($topic, 0, 60),
                ]);

                $handler->handle($payload);
            });
        } catch (UniqueConstraintViolationException) {
            $this->log($logger, $handler->operation(), LogStatus::Duplicate, 200, $request);

            return response()->json([]);
        }

        return response()->json([]);
    }

    /** The topic map lives in config/webhooks.php; the controller never names a topic. */
    private function handlerFor(string $topic): ?WebhookHandler
    {
        // Read the whole map: topics contain dots, which config() would take as nesting.
        $class = config('webhooks.handlers', [])[$topic] ?? null;

        return is_string($class) ? app($class) : null;
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
