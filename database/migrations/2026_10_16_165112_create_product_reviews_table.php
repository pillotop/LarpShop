<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id('review_id');

            $table->foreignId('product_id')
                  ->constrained('products', 'product_id')
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users', 'user_id')
                  ->restrictOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE product_reviews
             ADD CONSTRAINT reviews_rating_check
             CHECK (rating BETWEEN 1 AND 5)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE reviews DROP CONSTRAINT IF EXISTS check_rating_range');
        Schema::dropIfExists('product_reviews');
    }
};
