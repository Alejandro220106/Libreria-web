<?php

use App\Providers\AppServiceProvider;
use Src\Catalog\Infrastructure\CatalogServiceProvider;
use Src\IntegrationLog\Infrastructure\IntegrationLogServiceProvider;
use Src\Orders\Infrastructure\OrdersServiceProvider;

return [
    AppServiceProvider::class,
    IntegrationLogServiceProvider::class,
    CatalogServiceProvider::class,
    OrdersServiceProvider::class,
];
