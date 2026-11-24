<?php

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\ConnectorResult;

/*
| Architecture rules from docs/arquitectura.md (#75, #167).
| Pest reads the source code without running it, so these tests need no database or network.
*/

$modules = ['Catalog', 'Orders', 'IntegrationLog', 'Webhooks'];

// The center of the hexagon: each module's domain is plain PHP.
foreach ($modules as $module) {
    arch("the {$module} domain does not depend on the framework or the adapters")
        ->expect("Src\\{$module}\\Domain")
        ->not->toUse(['Illuminate', 'Symfony', 'App', 'Src\\Catalog\\Infrastructure', 'Src\\Orders\\Infrastructure', 'Src\\IntegrationLog\\Infrastructure', 'Src\\Webhooks\\Infrastructure']);

    // Use cases reach the database and outside systems only through contracts (ports).
    arch("the {$module} use cases never use an adapter or Eloquent")
        ->expect("Src\\{$module}\\Application")
        ->not->toUse(['Illuminate\\Database', 'Illuminate\\Http', 'Illuminate\\Support\\Facades', 'Src\\Catalog\\Infrastructure', 'Src\\Orders\\Infrastructure', 'Src\\IntegrationLog\\Infrastructure', 'Src\\Webhooks\\Infrastructure']);
}

arch('repositories and ports are interfaces')
    ->expect(['Src\\Catalog\\Domain\\Contracts', 'Src\\Orders\\Domain\\Contracts', 'Src\\Catalog\\Application\\Contracts', 'Src\\Orders\\Application\\Contracts', 'Src\\IntegrationLog\\Application\\Contracts', 'Src\\Webhooks\\Application\\Contracts'])
    ->toBeInterfaces();

// Brief 6.6.2: the provider comes from CONECTOR_PROVEEDOR, so only the shared
// interface and its value objects may appear in business code, never a concrete connector.
$sharedConnectorTypes = [ConnectorInterface::class, ConnectorOrder::class, ConnectorResult::class];

arch('business code never names a concrete connector')
    ->expect('Src')
    ->not->toUse('App\\Conectores')
    ->ignoring([...$sharedConnectorTypes, 'Src\\Orders\\Infrastructure\\OrdersServiceProvider']);

// env() returns null once the configuration is cached for deployment (#64).
// The config/ files have no namespace, so they are not part of this check.
arch('env() is only used inside the config files')
    ->expect('env')
    ->not->toBeUsed();

arch('no debug calls are left in the code')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsed();
