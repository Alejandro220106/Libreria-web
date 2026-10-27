<?php

namespace App\Application\Ports;

/**
 * Port to read the store. Business code only knows this interface;
 * the WooCommerce adapter lives in Infrastructure.
 */
interface StoreClient
{
    /**
     * Sends a GET request to the store REST API.
     *
     * @param  string  $endpoint  Path after /wp-json/wc/v3/, for example "products".
     * @param  array<string, mixed>  $query  Query string parameters.
     * @return array{status: int, data: array<int|string, mixed>, total_pages: int}
     */
    public function get(string $endpoint, array $query = []): array;
}
