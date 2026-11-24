<?php

namespace App\Conectores;

use Src\IntegrationLog\Application\Contracts\IntegrationLogger;
use Src\IntegrationLog\Domain\Enums\LogDirection;
use Src\IntegrationLog\Domain\Enums\LogStatus;
use Src\IntegrationLog\Domain\Enums\LogSystem;
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

    /** Telegram rejects a text longer than 4096 characters. */
    private const MAX_LENGTH = 4096;

    /**
     * A virtual invoice of the order: products, amounts, date and shipping location.
     * Never the customer's name, email or phone (contract C8): the engine does not keep them.
     */
    private function message(ConnectorOrder $order): string
    {
        $lines = [
            'LibroLocura · Factura virtual (ambiente de pruebas)',
            "Pedido: #{$order->orderId}",
        ];

        if ($order->orderedAt !== null) {
            $lines[] = 'Fecha: '.date('d/m/Y H:i', (int) strtotime($order->orderedAt));
        }

        if ($order->status !== '') {
            $lines[] = 'Estado: '.$order->status;
        }

        if ($order->lines !== []) {
            $lines[] = '';
            $lines[] = 'Detalle:';
            $sum = 0.0;

            foreach ($order->lines as $line) {
                $sum += (float) $line['subtotal'];
                $lines[] = "• {$line['quantity']} x {$line['name']}"
                    .($line['sku'] ? " ({$line['sku']})" : '')
                    .' — '.$this->money($line['unit_price'], $order->currency)
                    .' c/u = '.$this->money($line['subtotal'], $order->currency);
            }

            $lines[] = '';
            $lines[] = 'Subtotal: '.$this->money($sum, $order->currency);

            // The engine keeps only the grand total; the difference is shipping and other charges.
            $extra = (float) $order->total - $sum;
            if ($extra > 0) {
                $lines[] = 'Envío y otros cargos: '.$this->money($extra, $order->currency);
            }
        }

        $lines[] = 'Total: '.$this->money($order->total, $order->currency);
        $lines = [...$lines, '', ...$this->shipping($order)];

        return mb_substr(implode("\n", $lines), 0, self::MAX_LENGTH);
    }

    /** @return list<string> */
    private function shipping(ConnectorOrder $order): array
    {
        $lines = ['Envío:'];

        if ($order->address !== null) {
            $lines[] = "Provincia: {$order->address['provincia']}";
            $lines[] = "Cantón: {$order->address['canton']}";
            $lines[] = "Distrito: {$order->address['distrito']}";

            if (($order->address['otras_senas'] ?? '') !== '') {
                $lines[] = "Otras señas: {$order->address['otras_senas']}";
            }

            return $lines;
        }

        if ($order->addressText !== '') {
            $lines[] = "Dirección: {$order->addressText}";
        }

        if ($order->addressStatus === 'pending_review') {
            $lines[] = 'La dirección quedó pendiente de revisión.';
        }

        return $lines;
    }

    private function money(mixed $amount, string $currency): string
    {
        $formatted = number_format((float) $amount, 2, ',', '.');

        return $currency === 'CRC' ? "₡{$formatted}" : "{$formatted} {$currency}";
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
