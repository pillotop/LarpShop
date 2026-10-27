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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id('payment_event_id');

            $table->foreignId('payment_id')
                  ->constrained('payments', 'payment_id')
                  ->restrictOnDelete();

            $table->string('event_type', 50);
            $table->string('status', 30)->nullable();
            $table->string('provider_event_id')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'provider_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
