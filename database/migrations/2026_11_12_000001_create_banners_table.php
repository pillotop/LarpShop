<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id('banner_id');
            $table->foreignId('vendor_id')->constrained('vendors', 'vendor_id')->restrictOnDelete();
            $table->string('zone', 64);
            $table->string('title');
            $table->string('alt_text');
            $table->string('image_disk', 32)->default('public');
            $table->string('image_path');
            $table->unsignedInteger('image_width');
            $table->unsignedInteger('image_height');
            $table->string('link_url', 2048)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vendor_id', 'zone', 'is_active', 'sort_order'], 'banners_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
