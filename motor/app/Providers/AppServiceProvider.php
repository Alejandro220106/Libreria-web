<?php

namespace App\Providers;

use App\Application\Ports\IntegrationLogger;
use App\Application\Ports\StoreClient;
use App\Infrastructure\Logging\DatabaseIntegrationLogger;
use App\Infrastructure\Store\WooCommerceClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StoreClient::class, WooCommerceClient::class);
        $this->app->bind(IntegrationLogger::class, DatabaseIntegrationLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
