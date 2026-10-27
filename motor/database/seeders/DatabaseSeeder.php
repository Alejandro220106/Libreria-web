<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the data the Newman collection expects.
     */
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
            IntegrationLogSeeder::class,
        ]);
    }
}
