<?php

use Illuminate\Support\Facades\Route;

// Every route in this file is served under the /api prefix.
// Contract endpoints go inside the v1 group (brief 6.3); each module owns its routes.
Route::prefix('v1')->group(function () {
    require base_path('src/Catalog/Infrastructure/Http/routes.php');
    require base_path('src/Orders/Infrastructure/Http/routes.php');
    require base_path('src/IntegrationLog/Infrastructure/Http/routes.php');
    require base_path('src/Webhooks/Infrastructure/Http/routes.php');
});
