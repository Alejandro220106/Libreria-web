<?php

namespace App\Console\Commands;

use App\Application\Products\SyncProducts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class SyncProductsCommand extends Command
{
    protected $signature = 'tienda:sincronizar-productos';

    protected $description = 'Copy every store product into the engine database';

    public function handle(SyncProducts $sync): int
    {
        $count = $sync->handle(Date::now()->utc());

        $this->info("Synced {$count} products.");

        return self::SUCCESS;
    }
}
