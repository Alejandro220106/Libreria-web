<?php

use App\Http\Controllers\LogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

// Every route in this file is served under the /api prefix.
// Contract endpoints go inside the v1 group (brief 6.3).
Route::prefix('v1')->group(function () {
    Route::get('productos', [ProductController::class, 'index']);
    // Only digits reach the controller; anything else is a 404 in the contract format.
    Route::get('pedidos/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::get('bitacora', [LogController::class, 'index']);
    Route::post('webhooks/woocommerce', WooCommerceWebhookController::class);
});
