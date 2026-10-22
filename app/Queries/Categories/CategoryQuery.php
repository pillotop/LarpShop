<?php

namespace App\Queries\Categories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryQuery
{
    public function forNavigation(): Collection
    {
        return Category::query()->orderBy('category_id')->get();
    }
}
