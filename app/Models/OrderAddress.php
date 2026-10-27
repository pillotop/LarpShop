<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    protected $primaryKey = 'order_address_id';
    protected $fillable = [
        'type',
        'recipient_name',
        'phone_number',
        'address_line_1',
        'address_line_2',
        'city',
        'region',
        'postal_code',
        'country_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
