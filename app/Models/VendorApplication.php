<?php

namespace App\Models;

use App\Enums\Vendors\DeliveryMode;
use App\Enums\Vendors\OnboardingStep;
use App\Enums\Vendors\SellerType;
use App\Enums\Vendors\VendorApplicationStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VendorApplication extends Model
{
    use HasFactory;

    protected $primaryKey = 'vendor_application_id';

    protected $fillable = [
        'current_step', 'seller_type', 'seller_country_code', 'business_name',
        'contact_name', 'contact_email', 'contact_phone', 'store_name', 'description',
        'pickup_recipient_name', 'pickup_phone_number', 'pickup_country_code',
        'pickup_address_line_1', 'pickup_address_line_2', 'pickup_city',
        'pickup_region', 'pickup_postal_code', 'delivery_mode',
    ];

    protected $attributes = ['status' => 'draft', 'current_step' => 'seller'];

    protected $appends = ['profile_image_url'];

    protected function profileImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->brand_path
            ? Storage::disk('public')->url($this->brand_path)
            : asset('images/default-vendor.svg'));
    }

    protected function casts(): array
    {
        return [
            'status' => VendorApplicationStatus::class,
            'current_step' => OnboardingStep::class,
            'seller_type' => SellerType::class,
            'delivery_mode' => DeliveryMode::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(VendorApplicationDestination::class, 'vendor_application_id', 'vendor_application_id');
    }
}
