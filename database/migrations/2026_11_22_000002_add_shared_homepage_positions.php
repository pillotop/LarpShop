<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['banner_group_placements', 'promo_sections'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('homepage_position')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['banner_group_placements', 'promo_sections'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('homepage_position');
            });
        }
    }
};
