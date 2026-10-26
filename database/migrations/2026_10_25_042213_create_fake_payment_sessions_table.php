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
        Schema::create('fake_payment_sessions', function (Blueprint $table) {
            $table->id();

            $table->uuid('reference')->unique();
            $table->string('status', 30)->default('pending');
            $table->string('final_outcome', 30)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fake_payment_sessions');
    }
};
