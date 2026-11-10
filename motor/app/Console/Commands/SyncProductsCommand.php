<?php

namespace App\Console\Commands;

use App\Application\Products\SyncProducts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class SyncProductsCommand extends Command
{
    protected $signature = 'tienda:sincronizar-productos
        {--incremental : Only copy the products changed since the last successful sync}';

    protected $description = 'Copy the store products into the engine database';

    public function handle(SyncProducts $sync): int
    {
        $since = null;

        if ($this->option('incremental')) {
            $since = $sync->lastSync();

            if ($since === null) {
                $this->warn('No previous sync found, running a full sync.');
            }
        }

        $count = $sync->handle(Date::now(), $since);

        $this->info($since === null
            ? "Synced {$count} products."
            : "Synced {$count} products changed since {$since->toIso8601String()}.");

        return self::SUCCESS;
    }
}
