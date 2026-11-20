<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Promotions\PromoSectionQuery;
use App\Services\Products\ProductRetrievalService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService) {}

    public function index(PromoSectionQuery $sections): View
    {
        $products = $this->productRetrievalService->getProducts();

        return view('homepage', [
            'productCategories' => Category::query()->get(),
            'products' => $products,
            'promoSections' => $sections->visibleForPlacement('homepage_main'),
        ]);
    }
}
