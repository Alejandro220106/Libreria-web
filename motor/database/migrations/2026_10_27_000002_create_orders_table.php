<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // WooCommerce order id; unique so a repeated webhook updates the same row.
            $table->unsignedBigInteger('woo_id')->unique();
            $table->string('status', 30);
            $table->string('currency', 3)->default('CRC');
            $table->decimal('total', 12, 2);
            $table->json('lines');

            // Delivery address: raw text, processing status and the AI result.
            $table->text('address_text');
            // unprocessed | normalized | pending_review
            $table->string('address_status', 20)->default('unprocessed')->index();
            $table->json('address_normalized')->nullable();

            // Result of the connector action for this order.
            // pending | success | failure
            $table->string('connector_status', 20)->default('pending');
            $table->string('connector_detail')->nullable();
            $table->string('connector_reference')->nullable();
            $table->timestamp('connector_updated_at')->nullable();

            $table->timestamp('ordered_at');
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
