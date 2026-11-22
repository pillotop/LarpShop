<?php

namespace App\Models;

use App\Enums\PromoSource;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoSection extends Model
{
    use HasFactory;

    protected $primaryKey = 'promo_section_id';

    protected $attributes = ['source' => 'collection'];

    // Collection membership is assigned through the relationship by the management flow.
    protected $fillable = [
        'heading', 'introduction', 'placement', 'layout', 'is_active', 'source',
        'starts_at', 'expires_at', 'sort_order', 'image_disk', 'image_path',
        'alt_text', 'image_width', 'image_height', 'image_variants',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'sort_order' => 'integer',
            'homepage_position' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'image_variants' => 'array',
        ];
    }

    public function displayHeading(): string
    {
        return $this->heading ?: ($this->collection?->name
            ?? PromoSource::tryFrom($this->source)?->label() ?? __('promotions.heading'));
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'product_collection_id', 'product_collection_id');
    }

    public function bannerGroup(): BelongsTo
    {
        return $this->belongsTo(BannerGroup::class, 'banner_group_id', 'banner_group_id');
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
