<?php

use Illuminate\Support\Facades\Route;
use Src\Webhooks\Infrastructure\Http\Controllers\WooCommerceWebhookController;

// Loaded inside the /api/v1 group of routes/api.php.
Route::post('webhooks/woocommerce', WooCommerceWebhookController::class);
