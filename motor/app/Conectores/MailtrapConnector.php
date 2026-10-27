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
 * Messaging connector: notifies each new order with an email sent to the Mailtrap sandbox.
 */
class MailtrapConnector implements ConnectorInterface
{
    public function __construct(private readonly IntegrationLogger $logger) {}

    public function execute(ConnectorOrder $order): ConnectorResult
    {
        $config = config('connector.mailtrap');
        $started = microtime(true);
        $attempts = 1;

        try {
            $response = Http::withToken((string) $config['token'])
                ->acceptJson()
                ->timeout((int) config('connector.timeout'))
                ->retry(
                    (int) config('connector.tries'),
                    function (int $attempt) use (&$attempts): int {
                        $attempts = $attempt + 1;

                        return $attempt * (int) config('connector.backoff_ms');
                    },
                    when: fn (Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException && ($e->response->serverError() || $e->response->status() === 429)),
                )
                ->post(rtrim((string) $config['url'], '/').'/'.$config['inbox_id'], [
                    'from' => ['email' => $config['from_email'], 'name' => $config['from_name']],
                    'to' => [['email' => $config['to_email']]],
                    'subject' => "Pedido #{$order->orderId} recibido",
                    'text' => "Gracias por su compra. Total: {$order->total} {$order->currency}.",
                ]);
        } catch (Throwable $e) {
            $code = $e instanceof RequestException ? $e->response->status() : null;
            $this->log($order, $started, $attempts, LogStatus::Failure, $code, $e::class);

            // The failure is reported, not thrown: the order stays saved.
            return ConnectorResult::failure($code === null ? 'No connection to the provider' : "The provider answered {$code}");
        }

        $this->log($order, $started, $attempts, LogStatus::Success, $response->status());

        return ConnectorResult::success($response->json('message_ids.0'));
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
