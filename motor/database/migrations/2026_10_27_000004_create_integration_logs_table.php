<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            // incoming | outgoing
            $table->string('direction', 10);
            // store | connector | ai | legacy
            $table->string('system', 20);
            $table->string('operation', 60);
            // success | failure | duplicate | invalid_signature
            $table->string('status', 20);
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('correlation_id', 100)->nullable();
            // Never store keys, secrets, signatures or full request bodies here.
            $table->text('error')->nullable();
            // UTC date of the exchange.
            $table->timestamp('logged_at');

            $table->index('logged_at');
            $table->index('system');
            $table->index('status');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
