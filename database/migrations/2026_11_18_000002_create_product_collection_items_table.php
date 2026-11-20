<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collection_items', function (Blueprint $table) {
            $table->foreignId('product_collection_id')
                ->constrained('product_collections', 'product_collection_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['product_collection_id', 'product_id'], 'collection_items_primary');
            $table->index(['product_collection_id', 'sort_order', 'product_id'], 'collection_items_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collection_items');
    }
};
