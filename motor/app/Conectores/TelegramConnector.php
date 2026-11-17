<?php

namespace App\Conectores;

use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Messaging connector: notifies each new order to the team's test chat through the
 * Telegram Bot API (docs/investigacion-1/correspondencia.md).
 *
 * The bot token travels in the URL path, so it must never reach the log: on failure
 * only the HTTP code and the exception class are recorded.
 */
class TelegramConnector implements ConnectorInterface
{
    public function __construct(private readonly IntegrationLogger $logger) {}

    public function execute(ConnectorOrder $order): ConnectorResult
    {
        $config = config('connector.telegram');
        $started = microtime(true);
        $attempts = 1;

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('connector.timeout'))
                ->retry(
                    (int) config('connector.tries'),
                    function (int $attempt, Throwable $e) use (&$attempts): int {
                        $attempts = $attempt + 1;

                        // On a 429 Telegram says how long to wait; otherwise use the growing backoff.
                        $retryAfter = $e instanceof RequestException
                            ? (int) $e->response->json('parameters.retry_after', 0)
                            : 0;

                        return $retryAfter > 0
                            ? $retryAfter * 1000
                            : $attempt * (int) config('connector.backoff_ms');
                    },
                    when: fn (Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException && ($e->response->serverError() || $e->response->status() === 429)),
                )
                ->post(rtrim((string) $config['url'], '/').'/bot'.$config['token'].'/sendMessage', [
                    'chat_id' => $config['chat_id'],
                    'text' => $this->message($order),
                ]);
        } catch (Throwable $e) {
            $code = $e instanceof RequestException ? $e->response->status() : null;
            $this->log($order, $started, $attempts, LogStatus::Failure, $code, $e::class);

            // The failure is reported, not thrown: the order stays saved.
            return ConnectorResult::failure($code === null ? 'No connection to the provider' : "The provider answered {$code}");
        }

        $this->log($order, $started, $attempts, LogStatus::Success, $response->status());

        $messageId = $response->json('result.message_id');

        return ConnectorResult::success($messageId === null ? null : (string) $messageId);
    }

    /** Order number and total only: no customer personal data leaves the engine (contract C8). */
    private function message(ConnectorOrder $order): string
    {
        $total = number_format((float) $order->total, 2, '.', '');

        return "Gracias por su compra.\nPedido: #{$order->orderId}\nTotal: {$total} {$order->currency}";
    }

    private function log(ConnectorOrder $order, float $started, int $attempts, LogStatus $status, ?int $code, ?string $error = null): void
    {
        $this->logger->record(
            direction: LogDirection::Outgoing,
            system: LogSystem::Connector,
            operation: 'run_action',
            status: $status,
            httpCode: $code,
            attempts: $attempts,
            durationMs: (int) round((microtime(true) - $started) * 1000),
            correlationId: (string) $order->orderId,
            error: $error,
        );
    }
}
