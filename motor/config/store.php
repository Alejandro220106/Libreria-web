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

    // Verify the store's TLS certificate. Keep true everywhere except a local store
    // (for example LocalWP) whose certificate is self-signed: set false only in your own .env.
    'verify_ssl' => filter_var(env('STORE_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),

    // Products per page when syncing.
    'per_page' => (int) env('STORE_PER_PAGE', 100),

];
