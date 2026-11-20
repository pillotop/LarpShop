<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_sections', function (Blueprint $table) {
            $table->id('promo_section_id');
            $table->foreignId('product_collection_id')
                ->constrained('product_collections', 'product_collection_id')->restrictOnDelete();
            $table->string('heading', 150)->nullable();
            $table->text('introduction')->nullable();
            $table->string('placement', 64)->default('homepage_main');
            $table->string('layout', 32)->default('grid');
            $table->string('image_disk', 32)->default('public');
            $table->string('image_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->json('image_variants')->nullable();
            $table->boolean('is_active')->default(false);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['placement', 'is_active', 'sort_order', 'promo_section_id'], 'promo_sections_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_sections');
    }
};
