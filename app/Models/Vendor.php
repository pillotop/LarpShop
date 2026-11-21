<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Vendor extends Model
{
    protected $primaryKey = 'vendor_id';

    protected $fillable = [
        'user_id',
        'store_name',
        'description',
        'status',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id', 'vendor_id');
    }

    public function application(): HasOne
    {
        return $this->hasOne(VendorApplication::class, 'vendor_id', 'vendor_id');
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class, 'vendor_id', 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'approved']);
    }

    protected function profileImageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->brand_path
                ? Storage::disk('public')->url($this->brand_path)
                : asset('images/default-vendor.svg')
        );
    }
}
