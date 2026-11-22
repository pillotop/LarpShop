<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_system_blocks', function (Blueprint $table) {
            $table->id('homepage_system_block_id');
            $table->string('block_key')->unique();
            $table->unsignedInteger('homepage_position')->nullable();
        });
        DB::table('homepage_system_blocks')->insert([
            ['block_key' => 'broad_select', 'homepage_position' => null],
            ['block_key' => 'category_discovery', 'homepage_position' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_system_blocks');
    }
};
