<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $primaryKey = 'address_id';
    protected $fillable = [
        'recipient_name',
        'phone_number',
        'address_line_1',
        'address_line_2',
        'city',
        'region',
        'postal_code',
        'country_code',
        'is_default',
    ];


    protected function casts(): array
    {
        return [ 'is_default' => 'boolean' ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function snapshot(): array
    {
        return $this->only([
            'recipient_name',
            'phone_number',
            'address_line_1',
            'address_line_2',
            'city',
            'region',
            'postal_code',
            'country_code',
        ]);
    }
}
