<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Every route in this file is served under the /api prefix.
// Contract endpoints go inside the v1 group (brief 6.3).
Route::prefix('v1')->group(function () {
    Route::get('productos', [ProductController::class, 'index']);
});
