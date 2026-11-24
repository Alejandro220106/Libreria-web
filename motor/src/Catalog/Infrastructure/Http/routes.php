<?php

use Illuminate\Support\Facades\Route;
use Src\Catalog\Infrastructure\Http\Controllers\ProductController;

// Loaded inside the /api/v1 group of routes/api.php.
Route::get('productos', [ProductController::class, 'index']);
