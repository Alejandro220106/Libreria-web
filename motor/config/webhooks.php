<?php

use App\Application\Webhooks\OrderCreatedHandler;
use App\Application\Webhooks\OrderUpdatedHandler;

return [

    // WooCommerce topic (X-WC-Webhook-Topic header) => handler class (#77).
    // A topic missing here is answered 200 and logged, so WooCommerce does not
    // count failed deliveries and pause the webhook.
    'handlers' => [
        'order.created' => OrderCreatedHandler::class,
        'order.updated' => OrderUpdatedHandler::class,
    ],

];
