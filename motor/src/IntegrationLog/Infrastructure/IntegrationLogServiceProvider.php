<?php

namespace Src\IntegrationLog\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Src\IntegrationLog\Application\Contracts\IntegrationLogger;
use Src\IntegrationLog\Infrastructure\Persistence\DatabaseIntegrationLogger;

/** Connects the integration-log port to its adapter. Every other module writes through it. */
class IntegrationLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IntegrationLogger::class, DatabaseIntegrationLogger::class);
    }
}
