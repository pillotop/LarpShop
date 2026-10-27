<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UC-34 & UC-35: Per-item fulfillment and shipping lifecycle for multi-vendor orders.
     */
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('fulfillment_status', 20)
                ->default('unfulfilled')
                ->after('unit_price');
            $table->timestamp('shipped_at')
                ->nullable()
                ->after('fulfillment_status');
            $table->timestamp('delivered_at')
                ->nullable()
                ->after('shipped_at');

            $table->index('fulfillment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn(['fulfillment_status', 'shipped_at', 'delivered_at']);
        });
    }
};
