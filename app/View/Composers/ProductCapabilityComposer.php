<?php

namespace App\View\Composers;

use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\CartRepository;
use Illuminate\View\View;

class ProductCapabilityComposer
{
    public function __construct(
        private CategoryRepository $categories,
        private ProductRepository $products,
        private CartRepository $carts,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('cart', $this->carts->request());
    }
}
