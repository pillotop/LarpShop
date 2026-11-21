<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Promotions\PromoSectionQuery;
use App\Services\Products\ProductRetrievalService;
use App\Services\Promotions\BroadSelectService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService) {}

    public function index(PromoSectionQuery $sections, BroadSelectService $broadSelect): View
    {
        $products = $this->productRetrievalService->getProducts();

        return view('homepage', [
            'productCategories' => Category::query()->get(),
            'products' => $products,
            'broadSelectGroups' => $broadSelect->forHomepage(),
            'promoSections' => $sections->visibleForPlacement('homepage_main'),
        ]);
    }
}
