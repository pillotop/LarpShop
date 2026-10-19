<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    public function __construct()
    {
    }

    public function count(): int
    {
        return DB::table('categories')->count();
    }
}
