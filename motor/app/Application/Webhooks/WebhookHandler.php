<?php

namespace App\Application\Webhooks;

/**
 * Handles one WooCommerce webhook topic (#77).
 *
 * The controller has already checked the signature and recorded the delivery, so a
 * handler receives only the verified payload. Supporting a new topic means adding one
 * class like this and one entry in config/webhooks.php.
 */
interface WebhookHandler
{
    /** snake_case name used in the integration log, for example "order_created_webhook". */
    public function operation(): string;

    /** Runs inside the delivery's transaction: if it throws, the delivery is not recorded. */
    public function handle(array $payload): void;
}
