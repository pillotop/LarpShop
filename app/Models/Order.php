<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'total_amount',
    'subtotal_amount',
    'discount_amount',
    'voucher_id',
    'voucher_code',
    'status',
    'shipping_method',
    'shipping_fee',
    'expires_at',
    'stock_released_at',
])]
class Order extends Model
{
    protected $primaryKey = 'order_id';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id', 'order_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class, 'order_id', 'order_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id', 'voucher_id');
    }

    public function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'status' => OrderStatus::class,
            'expires_at' => 'datetime',
            'stock_released_at' => 'datetime',
        ];
    }
}
