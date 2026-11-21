<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('source_amount', 12, 2)->nullable();
            $table->char('source_currency', 3)->nullable();
            $table->decimal('exchange_rate', 20, 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['source_amount', 'source_currency', 'exchange_rate']);
        });
    }
};
