<?php

namespace Src\Orders\Infrastructure;

use App\Conectores\ConnectorInterface;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Src\Orders\Application\Contracts\AddressNormalizer;
use Src\Orders\Domain\Contracts\OrderRepository;
use Src\Orders\Infrastructure\Ai\OllamaAddressNormalizer;
use Src\Orders\Infrastructure\Persistence\Repositories\EloquentOrderRepository;

/** Connects each Orders port to its adapter. The providers come from .env. */
class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);

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
}
