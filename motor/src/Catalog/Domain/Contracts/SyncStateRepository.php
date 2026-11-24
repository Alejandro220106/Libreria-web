<?php

namespace Src\Catalog\Domain\Contracts;

use Carbon\CarbonInterface;

/** Port to remember when each kind of sync last finished well. */
interface SyncStateRepository
{
    /** Start time of the last sync with this name that finished well, or null if none did. */
    public function lastSucceededAt(string $name): ?CarbonInterface;

    public function markSucceeded(string $name, CarbonInterface $startedAt): void;
}
