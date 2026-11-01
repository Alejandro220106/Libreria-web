<?php

namespace App\Application\Products;

use App\Application\Ports\StoreClient;
use App\Models\Product;
use Carbon\CarbonInterface;

/**
 * Copies every store product into the engine's database.
 *
 * It asks the store for one page at a time and writes that page with a single upsert
 * keyed by the WooCommerce id, so memory does not grow with the catalog and running it
 * twice updates the same rows instead of duplicating them.
 */
class SyncProducts
{
    /** The store calls these types in English; the contract uses Spanish. */
    private const TYPES = ['simple' => 'simple', 'variable' => 'variable', 'grouped' => 'agrupado', 'external' => 'externo'];

    public function __construct(private readonly StoreClient $store) {}

    /**
     * @return int Number of products written.
     */
    public function handle(CarbonInterface $now): int
    {
        $written = 0;
        $page = 1;
        $totalPages = 1;

        do {
            $response = $this->store->get('products', [
                'page' => $page,
                'per_page' => (int) config('store.per_page'),
            ]);

            $totalPages = $response['total_pages'];
            $rows = array_map(fn (array $product): array => $this->toRow($product, $now), $response['data']);

            if ($rows !== []) {
                Product::upsert(
                    $rows,
                    ['woo_id'],
                    ['sku', 'name', 'type', 'price', 'stock', 'categories', 'author', 'source_updated_at', 'synced_at'],
                );
                $written += count($rows);
            }

            $page++;
        } while ($page <= $totalPages);

        return $written;
    }

    /**
     * Maps one store product to a row of the products table.
     * upsert() skips the model casts, so the categories are encoded here.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function toRow(array $product, CarbonInterface $now): array
    {
        return [
            'woo_id' => $product['id'],
            // The store sends an empty string when there is no SKU; the column is unique and nullable.
            'sku' => ($product['sku'] ?? '') !== '' ? $product['sku'] : null,
            'name' => $product['name'],
            'type' => self::TYPES[$product['type'] ?? 'simple'] ?? 'simple',
            // Colones have no decimals. The store sends '' when there is no price.
            'price' => ($product['price'] ?? '') !== '' ? (int) round((float) $product['price']) : null,
            'stock' => $product['stock_quantity'] ?? null,
            'categories' => json_encode(array_column($product['categories'] ?? [], 'name'), JSON_UNESCAPED_UNICODE),
            // The plugin's own REST property (not meta_data).
            'author' => ($product['book_author'] ?? '') !== '' ? $product['book_author'] : null,
            'source_updated_at' => isset($product['date_modified_gmt'])
                ? $product['date_modified_gmt'].'Z'
                : null,
            'synced_at' => $now,
        ];
    }
}
