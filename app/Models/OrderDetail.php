<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'quantity',
    'unit_price',
    'fulfillment_status',
    'shipped_at',
    'delivered_at',
])]
class OrderDetail extends Model
{
    protected $table = 'order_details';

    protected $primaryKey = 'order_detail_id';

    protected $attributes = [
        'fulfillment_status' => 'unfulfilled',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'fulfillment_status' => FulfillmentStatus::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
