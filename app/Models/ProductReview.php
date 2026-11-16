<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $primaryKey = 'review_id';

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'vendor_reply',
        'vendor_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'vendor_replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
