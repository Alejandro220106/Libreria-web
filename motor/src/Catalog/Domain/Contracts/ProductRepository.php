<?php

namespace Src\Catalog\Domain\Contracts;

/**
 * Port to store the engine's copy of the catalog. The use case decides what a product
 * looks like; the adapter decides how it is saved (today, Eloquent on PostgreSQL).
 */
interface ProductRepository
{
    /**
     * Inserts new products and updates existing ones, matched by their store id.
     * Running it twice with the same page leaves the same rows, never duplicates.
     *
     * @param  list<array{woo_id: int, sku: ?string, name: string, type: string, price: ?int, stock: ?int, categories: list<string>, author: ?string, source_updated_at: ?string, synced_at: \Carbon\CarbonInterface}>  $products
     */
    public function upsertMany(array $products): void;
}
