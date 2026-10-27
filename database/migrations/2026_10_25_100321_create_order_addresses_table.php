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
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id('order_address_id');

            $table->foreignId('order_id')
                  ->constrained('orders', 'order_id')
                  ->restrictOnDelete();

            $table->string('type', 20)->default('shipping');
            $table->string('recipient_name', 100);
            $table->string('phone_number', 30)->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->char('country_code', 2);

            $table->unique(['order_id', 'type']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
