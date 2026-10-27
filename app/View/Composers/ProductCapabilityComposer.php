<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Services\Cart\CartRetrievalService;
use Illuminate\View\View;

class ProductCapabilityComposer
{
    public function __construct(
        private CartRetrievalService $carts,
    ) {}

    public function compose(View $view): void
    {
        $view->with('cart', $this->carts->getCartData());
        $view->with('productCategories', Category::query()->get());
    }
}
