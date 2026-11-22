<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BannerGroup extends Model
{
    protected $primaryKey = 'banner_group_id';

    protected $fillable = ['slug', 'name', 'layout', 'is_active', 'starts_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BannerCard::class, 'banner_group_id', 'banner_group_id')
            ->orderBy('sort_order')->orderBy('banner_card_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(BannerGroupPlacement::class, 'banner_group_id', 'banner_group_id')
            ->orderBy('sort_order')->orderBy('banner_group_placement_id');
    }

    public function promoSections(): HasMany
    {
        return $this->hasMany(PromoSection::class, 'banner_group_id', 'banner_group_id');
    }

    public function scopeVisibleAt(Builder $query, CarbonInterface $at): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('layout', array_keys(config('banners.layouts', [])))
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $at));
    }
}
