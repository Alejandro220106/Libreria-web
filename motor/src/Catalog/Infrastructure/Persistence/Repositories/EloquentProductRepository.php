<?php

namespace Src\Catalog\Infrastructure\Persistence\Repositories;

use Src\Catalog\Domain\Contracts\ProductRepository;
use Src\Catalog\Infrastructure\Persistence\Models\Product;

class EloquentProductRepository implements ProductRepository
{
    public function upsertMany(array $products): void
    {
        if ($products === []) {
            return;
        }

        // upsert() skips the model casts, so the categories are encoded here.
        $rows = array_map(fn (array $product): array => [
            ...$product,
            'categories' => json_encode($product['categories'], JSON_UNESCAPED_UNICODE),
        ], $products);

        // One query per page, keyed by the WooCommerce id.
        Product::upsert(
            $rows,
            ['woo_id'],
            ['sku', 'name', 'type', 'price', 'stock', 'categories', 'author', 'source_updated_at', 'synced_at'],
        );
    }
}
