<?php

namespace Src\Catalog\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Src\Catalog\Application\Contracts\StoreClient;
use Src\Catalog\Domain\Contracts\ProductRepository;
use Src\Catalog\Domain\Contracts\SyncStateRepository;
use Src\Catalog\Infrastructure\Persistence\Repositories\EloquentProductRepository;
use Src\Catalog\Infrastructure\Persistence\Repositories\EloquentSyncStateRepository;
use Src\Catalog\Infrastructure\WooCommerce\WooCommerceClient;

/** Connects each Catalog port to its adapter. */
class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StoreClient::class, WooCommerceClient::class);
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(SyncStateRepository::class, EloquentSyncStateRepository::class);
    }
}
