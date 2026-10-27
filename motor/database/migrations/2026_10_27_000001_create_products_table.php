<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // WooCommerce product id; unique so syncing can upsert by it.
            $table->unsignedBigInteger('woo_id')->unique();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('name', 200);
            $table->string('type', 20)->default('simple');
            // Money is stored as decimal, never float.
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->json('categories');
            // The store plugin's custom field (the book's author).
            $table->string('author', 255)->nullable();
            // Kept so the Capstone reconciliation can compare both sides.
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
