<?php

namespace Src\Catalog\Infrastructure\Persistence\Repositories;

use Carbon\CarbonInterface;
use Src\Catalog\Domain\Contracts\SyncStateRepository;
use Src\Catalog\Infrastructure\Persistence\Models\SyncState;

class EloquentSyncStateRepository implements SyncStateRepository
{
    public function lastSucceededAt(string $name): ?CarbonInterface
    {
        return SyncState::where('name', $name)->first()?->last_succeeded_at;
    }

    public function markSucceeded(string $name, CarbonInterface $startedAt): void
    {
        SyncState::updateOrCreate(['name' => $name], ['last_succeeded_at' => $startedAt]);
    }
}
