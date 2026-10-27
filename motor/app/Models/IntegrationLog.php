<?php

namespace App\Models;

use App\Domain\IntegrationLog\LogDirection;
use App\Domain\IntegrationLog\LogStatus;
use App\Domain\IntegrationLog\LogSystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * One row per exchange with another system. It never holds keys, secrets,
 * signatures or full request bodies.
 */
class IntegrationLog extends Model
{
    use Prunable;

    // The table has its own logged_at column instead of created_at / updated_at.
    public $timestamps = false;

    protected $fillable = [
        'direction',
        'system',
        'operation',
        'status',
        'http_code',
        'attempts',
        'duration_ms',
        'correlation_id',
        'error',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => LogDirection::class,
            'system' => LogSystem::class,
            'status' => LogStatus::class,
            'logged_at' => 'datetime',
        ];
    }

    /**
     * Records older than the retention period (config/integration_log.php).
     * The index on logged_at keeps the deletion fast.
     */
    public function prunable(): Builder
    {
        return static::where('logged_at', '<', now()->subDays(config('integration_log.retention_days')));
    }
}
