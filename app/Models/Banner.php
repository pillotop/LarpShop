<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $primaryKey = 'banner_id';

    // Ownership is assigned through the vendor relationship, never request input.
    protected $fillable = [
        'zone', 'title', 'alt_text', 'image_disk', 'image_path',
        'image_width', 'image_height', 'image_variants', 'link_url', 'starts_at', 'expires_at',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'is_active' => 'boolean',
            'image_variants' => 'array',
            'sort_order' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeScheduledAt(Builder $query, CarbonInterface $at): Builder
    {
        return $query
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $at));
    }
}
