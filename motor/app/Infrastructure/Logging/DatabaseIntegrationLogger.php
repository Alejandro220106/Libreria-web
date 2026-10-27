<?php

namespace App\Infrastructure\Logging;

use App\Application\Ports\IntegrationLogger;
use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use App\Models\IntegrationLog;

/** Stores the integration log in the integration_logs table. */
class DatabaseIntegrationLogger implements IntegrationLogger
{
    // A message is a hint, not a dump: long text could carry parts of a body.
    private const MAX_ERROR_LENGTH = 500;

    public function record(
        LogDirection $direction,
        LogSystem $system,
        string $operation,
        LogStatus $status,
        ?int $httpCode = null,
        int $attempts = 1,
        int $durationMs = 0,
        ?string $correlationId = null,
        ?string $error = null,
    ): void {
        IntegrationLog::create([
            'direction' => $direction,
            'system' => $system,
            'operation' => $operation,
            'status' => $status,
            'http_code' => $httpCode,
            'attempts' => $attempts,
            'duration_ms' => $durationMs,
            'correlation_id' => $correlationId,
            'error' => $error === null ? null : mb_substr($error, 0, self::MAX_ERROR_LENGTH),
            // Dates are stored in UTC (config/app.php timezone).
            'logged_at' => now(),
        ]);
    }
}
