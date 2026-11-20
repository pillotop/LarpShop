<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCollection extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_collection_id';

    protected $fillable = ['name', 'slug', 'description', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_collection_items', 'product_collection_id', 'product_id')
            ->withPivot('sort_order');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PromoSection::class, 'product_collection_id', 'product_collection_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
