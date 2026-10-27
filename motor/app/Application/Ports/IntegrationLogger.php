<?php

namespace App\Application\Ports;

use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;

/**
 * Port to record every exchange with another system. The store client, the connector,
 * the AI client and the webhook controller all write through this single interface.
 */
interface IntegrationLogger
{
    /**
     * @param  string  $operation  snake_case name, for example "list_products" or "order_created_webhook".
     * @param  string|null  $correlationId  Delivery id for webhooks, order id for outgoing calls.
     * @param  string|null  $error  Short message. Never keys, secrets, signatures or request bodies.
     */
    public function record(
        LogDirection $direction,
        LogSystem $system,
        string $operation,
        LogStatus $status,
        ?int $httpCode = null,
        int $attempts = 1,
        int $durationMs = 0,
        ?string $correlationId = null,
        ?string $error = null,
    ): void;
}
