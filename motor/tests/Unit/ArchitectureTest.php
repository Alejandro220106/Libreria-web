<?php

use App\Conectores\ConnectorInterface;
use App\Conectores\ConnectorOrder;
use App\Conectores\ConnectorResult;

/*
| Architecture rules from docs/arquitectura.md (#75).
| Pest reads the source code without running it, so these tests need no database or network.
*/

// Brief 6.6.2: the provider comes from CONECTOR_PROVEEDOR, so only the shared
// interface and its value objects may appear here, never a concrete connector.
// One rule per layer: with both namespaces in a single expect() Pest missed a violation.
$sharedConnectorTypes = [ConnectorInterface::class, ConnectorOrder::class, ConnectorResult::class];

arch('controllers never name a concrete connector')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Conectores')
    ->ignoring($sharedConnectorTypes);

arch('jobs never name a concrete connector')
    ->expect('App\Jobs')
    ->not->toUse('App\Conectores')
    ->ignoring($sharedConnectorTypes);

// env() returns null once the configuration is cached for deployment (#64).
// The config/ files have no namespace, so they are not part of this check.
arch('env() is only used inside the config files')
    ->expect('env')
    ->not->toBeUsed();

arch('the domain layer does not depend on the framework, HTTP or the database')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate', 'Symfony', 'App\Http', 'App\Models', 'App\Infrastructure', 'App\Jobs']);

arch('no debug calls are left in the code')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsed();
