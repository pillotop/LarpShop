<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_sections', function (Blueprint $table) {
            $table->string('source', 32)->default('collection');
            $table->unsignedBigInteger('product_collection_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Do not delete automatic sections or invent a collection during rollback.
        if (DB::table('promo_sections')->whereNull('product_collection_id')->exists()) {
            throw new RuntimeException('Assign a collection to or remove automatic promo sections before rolling back their source migration.');
        }
        Schema::table('promo_sections', function (Blueprint $table) {
            $table->unsignedBigInteger('product_collection_id')->nullable(false)->change();
            $table->dropColumn('source');
        });
    }
};
