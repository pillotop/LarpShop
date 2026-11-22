<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_groups', function (Blueprint $table) {
            $table->id('banner_group_id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('layout', 32);
            $table->boolean('is_active')->default(false);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('banner_cards', function (Blueprint $table) {
            $table->id('banner_card_id');
            $table->foreignId('banner_group_id')->constrained('banner_groups', 'banner_group_id')->cascadeOnDelete();
            $table->string('title');
            $table->string('eyebrow')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('alt_text');
            $table->json('translations')->nullable();
            $table->string('link_url', 2048);
            $table->string('presentation', 32)->default('top_copy');
            $table->string('text_tone', 16)->default('dark');
            $table->unsignedTinyInteger('focal_x')->default(50);
            $table->unsignedTinyInteger('focal_y')->default(50);
            $table->string('image_disk', 32)->default('public');
            $table->string('image_path');
            $table->unsignedInteger('image_width');
            $table->unsignedInteger('image_height');
            $table->json('image_variants')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['banner_group_id', 'is_active', 'sort_order']);
        });

        Schema::create('banner_group_placements', function (Blueprint $table) {
            $table->id('banner_group_placement_id');
            $table->foreignId('banner_group_id')->constrained('banner_groups', 'banner_group_id')->cascadeOnDelete();
            $table->string('placement', 64);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['banner_group_id', 'placement']);
            $table->index(['placement', 'sort_order']);
        });

        Schema::table('promo_sections', function (Blueprint $table) {
            $table->foreignId('banner_group_id')->nullable()
                ->constrained('banner_groups', 'banner_group_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('promo_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banner_group_id');
        });
        Schema::dropIfExists('banner_group_placements');
        Schema::dropIfExists('banner_cards');
        Schema::dropIfExists('banner_groups');
    }
};
