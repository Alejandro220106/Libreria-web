<?php

namespace App\Providers;

use App\Application\Ports\AddressNormalizer;
use App\Application\Ports\IntegrationLogger;
use App\Application\Ports\StoreClient;
use App\Infrastructure\Ai\OllamaAddressNormalizer;
use App\Infrastructure\Logging\DatabaseIntegrationLogger;
use App\Infrastructure\Store\WooCommerceClient;
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

        // The provider comes from AI_PROVIDER, so a new one touches no business code.
        $this->app->bind(AddressNormalizer::class, fn ($app) => match (config('ai.provider')) {
            'ollama' => $app->make(OllamaAddressNormalizer::class),
            default => throw new InvalidArgumentException('Unknown AI_PROVIDER.'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
