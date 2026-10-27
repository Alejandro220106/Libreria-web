<?php

namespace App\Http\Controllers;

use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Domain\Webhook\SignatureVerifier;
use App\Http\Responses\ApiError;
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
            $logger->record(
                direction: LogDirection::Incoming,
                system: LogSystem::Store,
                operation: 'order_created_webhook',
                status: LogStatus::InvalidSignature,
                httpCode: 401,
                // The header is controlled by the sender, so it is cut to the column size.
                correlationId: mb_substr((string) $request->header('X-WC-Webhook-Delivery-ID'), 0, 100) ?: null,
                error: 'The signature does not match',
            );

            return ApiError::response(401, 'firma_invalida', 'La firma del webhook no es válida.');
        }

        // Processing the delivery comes in the next issues (#42, #43).
        return response()->json([]);
    }
}
