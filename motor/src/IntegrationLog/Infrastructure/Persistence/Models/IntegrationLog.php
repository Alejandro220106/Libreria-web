<?php

namespace Src\IntegrationLog\Infrastructure\Persistence\Models;

use Src\IntegrationLog\Domain\Enums\LogDirection;
use Src\IntegrationLog\Domain\Enums\LogStatus;
use Src\IntegrationLog\Domain\Enums\LogSystem;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\IntegrationLogFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

// The model lives outside App\Models, so its factory is named explicitly.
/**
 * One row per exchange with another system. It never holds keys, secrets,
 * signatures or full request bodies.
 */
#[UseFactory(IntegrationLogFactory::class)]
class IntegrationLog extends Model
{
    use HasFactory, Prunable;

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
