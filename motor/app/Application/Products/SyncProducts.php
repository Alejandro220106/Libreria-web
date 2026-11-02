<?php

namespace App\Application\Products;

use App\Application\Ports\StoreClient;
use App\Models\Product;
use App\Models\SyncState;
use Carbon\CarbonInterface;

/**
 * Copies the store products into the engine's database.
 *
 * It asks the store for one page at a time and writes that page with a single upsert
 * keyed by the WooCommerce id, so memory does not grow with the catalog and running it
 * twice updates the same rows instead of duplicating them.
 *
 * With a $modifiedAfter date it asks only for the products changed after it (incremental).
 * Without it, it copies every product (the full sync the brief requires).
 */
class SyncProducts
{
    /** The store calls these types in English; the contract uses Spanish. */
    private const TYPES = ['simple' => 'simple', 'variable' => 'variable', 'grouped' => 'agrupado', 'external' => 'externo'];

    /** Row of sync_states that belongs to this sync. */
    private const STATE = 'products';

    public function __construct(private readonly StoreClient $store) {}

    /**
     * Start time of the last sync that finished well, or null if there was none.
     */
    public function lastSync(): ?CarbonInterface
    {
        return SyncState::where('name', self::STATE)->first()?->last_succeeded_at;
    }

    /**
     * @param  CarbonInterface  $now  Start time of this run (UTC).
     * @param  CarbonInterface|null  $modifiedAfter  Only products changed after this date; null for all.
     * @return int Number of products written.
     */
    public function handle(CarbonInterface $now, ?CarbonInterface $modifiedAfter = null): int
    {
        $written = 0;
        $page = 1;
        $totalPages = 1;

        do {
            $response = $this->store->get('products', [
                'page' => $page,
                'per_page' => (int) config('store.per_page'),
                ...$this->changedSince($modifiedAfter),
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

        // Only reached when every page worked: a failed call throws before this line,
        // so the stored date does not move forward and the next run asks again.
        // It saves the START time, so products edited while this run was going are not missed.
        SyncState::updateOrCreate(['name' => self::STATE], ['last_succeeded_at' => $now]);

        return $written;
    }

    /**
     * Query parameters that limit the request to changed products.
     * dates_are_gmt makes WooCommerce compare against the UTC modified date
     * instead of the store's local time.
     *
     * @return array<string, string>
     */
    private function changedSince(?CarbonInterface $modifiedAfter): array
    {
        if ($modifiedAfter === null) {
            return [];
        }

        return [
            'modified_after' => $modifiedAfter->toImmutable()->utc()->format('Y-m-d\TH:i:s'),
            'dates_are_gmt' => 'true',
        ];
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
