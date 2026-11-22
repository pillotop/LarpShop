<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSystemBlock extends Model
{
    protected $primaryKey = 'homepage_system_block_id';

    public $timestamps = false;

    protected $fillable = ['block_key', 'homepage_position'];

    protected function casts(): array
    {
        return ['homepage_position' => 'integer'];
    }
}
