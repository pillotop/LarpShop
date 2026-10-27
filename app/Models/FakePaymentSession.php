<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// just for testing so this has absolutely nothing meaningful
class FakePaymentSession extends Model
{
    protected $fillable = [
        'reference', 'status', 'final_outcome', 'ready_at'
    ];

    protected function casts(): array
    {
        return [
            'ready_at' => 'datetime',
        ];
    }
}
