<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerGroupPlacement extends Model
{
    protected $primaryKey = 'banner_group_placement_id';

    protected $fillable = ['placement', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'homepage_position' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(BannerGroup::class, 'banner_group_id', 'banner_group_id');
    }
}
