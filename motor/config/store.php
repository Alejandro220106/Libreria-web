<?php

return [

    // WooCommerce store (REST API v3).
    'url' => env('WOOCOMMERCE_URL'),
    'key' => env('WOOCOMMERCE_KEY'),
    'secret' => env('WOOCOMMERCE_SECRET'),

    // Secret that signs the webhooks the store sends us. Lives only in .env.
    'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET'),

    // Calls to the store (brief 6.4): 10 s timeout, 3 attempts, increasing backoff.
    'timeout' => (int) env('STORE_TIMEOUT', 10),
    'tries' => (int) env('STORE_TRIES', 3),
    'backoff_ms' => (int) env('STORE_BACKOFF_MS', 500),

    // Products per page when syncing.
    'per_page' => (int) env('STORE_PER_PAGE', 100),

];
