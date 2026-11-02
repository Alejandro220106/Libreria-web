<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_states', function (Blueprint $table) {
            $table->id();
            // What was synced, for example "products". One row per kind.
            $table->string('name', 60)->unique();
            // Start time (UTC) of the last run that finished without errors.
            $table->timestamp('last_succeeded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_states');
    }
};
