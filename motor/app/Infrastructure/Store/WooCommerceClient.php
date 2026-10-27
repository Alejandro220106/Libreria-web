<?php

namespace App\Infrastructure\Store;

use App\Application\Ports\IntegrationLogger;
use App\Application\Ports\StoreClient;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * WooCommerce REST API v3 adapter built on the Laravel HTTP client.
 *
 * Every value that may change (timeout, attempts, backoff) comes from config/store.php.
 */
class WooCommerceClient implements StoreClient
{
    public function __construct(private readonly IntegrationLogger $logger) {}

    public function get(string $endpoint, array $query = []): array
    {
        $url = rtrim((string) config('store.url'), '/').'/wp-json/wc/v3/'.ltrim($endpoint, '/');
        $started = microtime(true);
        $attempts = 1;

        try {
            $response = Http::withBasicAuth((string) config('store.key'), (string) config('store.secret'))
                ->acceptJson()
                ->timeout((int) config('store.timeout'))
                ->retry(
                    (int) config('store.tries'),
                    function (int $attempt) use (&$attempts): int {
                        $attempts = $attempt + 1;

                        // Increasing backoff: 1x, 2x, 3x the base delay.
                        return $attempt * (int) config('store.backoff_ms');
                    },
                    when: fn (Throwable $e): bool => $this->shouldRetry($e),
                )
                ->get($url, $query);
        } catch (Throwable $e) {
            $this->log($endpoint, $started, $attempts, $e instanceof RequestException ? $e->response->status() : null, $e);

            throw $e;
        }

        $this->log($endpoint, $started, $attempts, $response->status());

        return [
            'status' => $response->status(),
            'data' => $response->json() ?? [],
            'total_pages' => (int) $response->header('X-WP-TotalPages'),
        ];
    }

    /**
     * Only temporary failures are worth another attempt: no connection, 5xx and 429.
     * Any other 4xx means the request itself is wrong, so repeating it would not help.
     */
    private function shouldRetry(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return $e instanceof RequestException
            && ($e->response->serverError() || $e->response->status() === 429);
    }

    /**
     * Records the call in the integration log. It never includes keys or the URL:
     * the operation is only the first segment of the endpoint, for example "get_products".
     */
    private function log(string $endpoint, float $started, int $attempts, ?int $status, ?Throwable $error = null): void
    {
        $this->logger->record(
            direction: LogDirection::Outgoing,
            system: LogSystem::Store,
            operation: 'get_'.preg_replace('/[^a-z0-9]+/', '_', strtolower(explode('/', trim($endpoint, '/'))[0])),
            status: $error === null ? LogStatus::Success : LogStatus::Failure,
            httpCode: $status,
            attempts: $attempts,
            durationMs: (int) round((microtime(true) - $started) * 1000),
            error: $error === null ? null : $error::class,
        );
    }
}
