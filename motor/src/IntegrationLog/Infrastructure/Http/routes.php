<?php

use Illuminate\Support\Facades\Route;
use Src\IntegrationLog\Infrastructure\Http\Controllers\LogController;

// Loaded inside the /api/v1 group of routes/api.php.
Route::get('bitacora', [LogController::class, 'index']);
