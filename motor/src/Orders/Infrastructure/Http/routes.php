<?php

use Illuminate\Support\Facades\Route;
use Src\Orders\Infrastructure\Http\Controllers\OrderController;

// Loaded inside the /api/v1 group of routes/api.php.
// Only digits reach the controller; anything else is a 404 in the contract format.
Route::get('pedidos/{id}', [OrderController::class, 'show'])->whereNumber('id');
