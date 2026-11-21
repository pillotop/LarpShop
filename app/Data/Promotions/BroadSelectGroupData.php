<?php

namespace App\Data\Promotions;

use App\Models\Product;
use Illuminate\Support\Collection;

final readonly class BroadSelectGroupData
{
    /** @param Collection<int, Product> $products Already loaded products, never a query builder. */
    public function __construct(
        public string $key,
        public string $label,
        public string $source,
        public Collection $products,
        public ?string $viewAllUrl = null,
    ) {}
}
