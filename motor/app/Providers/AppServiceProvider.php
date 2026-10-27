<?php

namespace App\Providers;

use App\Application\Ports\IntegrationLogger;
use App\Conectores\ConnectorInterface;
use App\Application\Ports\StoreClient;
use App\Infrastructure\Logging\DatabaseIntegrationLogger;
use App\Infrastructure\Store\WooCommerceClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StoreClient::class, WooCommerceClient::class);
        $this->app->bind(IntegrationLogger::class, DatabaseIntegrationLogger::class);

        // The provider comes from CONECTOR_PROVEEDOR; nothing else names a concrete connector.
        $this->app->bind(ConnectorInterface::class, function ($app) {
            $name = (string) config('connector.provider');
            $class = config("connector.providers.{$name}")
                ?? throw new InvalidArgumentException("Unknown connector provider [{$name}].");

            return $app->make($class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates never change in place: addDay() returns a new instance.
        Date::use(CarbonImmutable::class);

        // Outside production, fail on lazy loading (N+1), unknown attributes and blocked mass assignment.
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
