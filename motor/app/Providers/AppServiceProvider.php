<?php

namespace App\Providers;

use App\Application\Ports\AddressNormalizer;
use App\Application\Ports\IntegrationLogger;
use App\Application\Ports\StoreClient;
use App\Conectores\ConnectorInterface;
use App\Infrastructure\Ai\OllamaAddressNormalizer;
use App\Infrastructure\Logging\DatabaseIntegrationLogger;
use App\Infrastructure\Store\WooCommerceClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
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
        // Dates never change in place: addDay() returns a new instance.
        Date::use(CarbonImmutable::class);

        // Outside production, fail on lazy loading (N+1), unknown attributes and blocked mass assignment.
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->runMigrationsAsSchemaOwner();
    }

    /**
     * The application role cannot create tables, so `php artisan migrate --seed` (the
     * documented startup step) switches to the schema owner by itself. Passing
     * --database still wins. Only the console migrate commands change; requests
     * always use the application role.
     */
    private function runMigrationsAsSchemaOwner(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (str_starts_with((string) $event->command, 'migrate')
                && ! $event->input->hasParameterOption('--database')
                && config('database.default') === 'pgsql') {
                config(['database.default' => 'pgsql_admin']);
            }
        });
    }
}
