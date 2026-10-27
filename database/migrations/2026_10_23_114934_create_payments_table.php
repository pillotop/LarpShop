<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');

            $table->foreignId('order_id')
                  ->constrained('orders', 'order_id')
                  ->restrictOnDelete();

            $table->string('provider', 50);
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('status')->default('pending'); // possible values: pending, processing, succeeded, failed, cancelled, refunded
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference']);
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
