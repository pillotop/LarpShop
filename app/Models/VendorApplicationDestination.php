<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorApplicationDestination extends Model
{
    protected $primaryKey = 'vendor_application_destination_id';

    public $timestamps = false;

    protected $fillable = ['country_code'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(VendorApplication::class, 'vendor_application_id', 'vendor_application_id');
    }
}
