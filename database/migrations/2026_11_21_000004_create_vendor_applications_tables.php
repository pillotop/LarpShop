<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_applications', function (Blueprint $table) {
            $table->id('vendor_application_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->unique()->constrained('vendors', 'vendor_id')->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->string('current_step', 20)->default('seller');
            $table->string('seller_type', 20)->nullable();
            $table->char('seller_country_code', 2)->nullable();
            $table->string('business_name', 150)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('store_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('brand_path')->nullable();
            $table->string('pickup_recipient_name', 100)->nullable();
            $table->string('pickup_phone_number', 30)->nullable();
            $table->char('pickup_country_code', 2)->nullable();
            $table->string('pickup_address_line_1')->nullable();
            $table->string('pickup_address_line_2')->nullable();
            $table->string('pickup_city', 100)->nullable();
            $table->string('pickup_region', 100)->nullable();
            $table->string('pickup_postal_code', 30)->nullable();
            $table->string('delivery_mode', 30)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('vendor_application_destinations', function (Blueprint $table) {
            $table->id('vendor_application_destination_id');
            $table->foreignId('vendor_application_id')
                ->constrained('vendor_applications', 'vendor_application_id')->cascadeOnDelete();
            $table->char('country_code', 2);
            $table->unique(['vendor_application_id', 'country_code'], 'vendor_application_destination_country_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_application_destinations');
        Schema::dropIfExists('vendor_applications');
    }
};
