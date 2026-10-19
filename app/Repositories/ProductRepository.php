<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function count(): int
    {
        return DB::table('categories')->count();
    }
}
