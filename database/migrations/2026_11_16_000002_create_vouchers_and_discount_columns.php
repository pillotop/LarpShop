<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id('voucher_id');
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors', 'vendor_id')
                ->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('title', 120);
            $table->string('type', 20);
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
            $table->index('vendor_id');
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id('voucher_usage_id');
            $table->foreignId('voucher_id')
                ->constrained('vouchers', 'voucher_id')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->cascadeOnDelete();
            $table->foreignId('order_id')
                ->constrained('orders', 'order_id')
                ->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->index(['voucher_id', 'user_id']);
            $table->unique('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 10, 2)
                ->default(0)
                ->after('total_amount');
            $table->decimal('discount_amount', 10, 2)
                ->default(0)
                ->after('subtotal_amount');
            $table->foreignId('voucher_id')
                ->nullable()
                ->after('discount_amount')
                ->constrained('vouchers', 'voucher_id')
                ->nullOnDelete();
            $table->string('voucher_code', 40)
                ->nullable()
                ->after('voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['subtotal_amount', 'discount_amount', 'voucher_code']);
        });

        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('vouchers');
    }
};
