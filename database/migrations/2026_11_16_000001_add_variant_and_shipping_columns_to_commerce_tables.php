<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('itemable_id')
                ->constrained('product_variants', 'variant_id')
                ->nullOnDelete();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants', 'variant_id')
                ->nullOnDelete();
            $table->string('variant_name', 150)
                ->nullable()
                ->after('variant_id');
            $table->string('variant_sku', 100)
                ->nullable()
                ->after('variant_name');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method', 30)
                ->default('home_delivery')
                ->after('status');
            $table->decimal('shipping_fee', 10, 2)
                ->default(0)
                ->after('shipping_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_method', 'shipping_fee']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn(['variant_name', 'variant_sku']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
        });
    }
};
