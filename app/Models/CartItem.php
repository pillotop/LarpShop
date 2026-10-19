<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CartItem extends Model
{
    protected $primaryKey = 'cart_item_id';
    protected $fillable = ['cart_id', 'itemable_type', 'itemable_id', 'quantity'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'cart_id');
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
