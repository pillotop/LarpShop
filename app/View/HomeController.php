<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Products\ProductRetrievalService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService) {}

    public function index(): View
    {
        $products = $this->productRetrievalService->getProducts();

        return view('homepage', [
            'productCategories' => Category::query()->get(),
            'products' => $products,
        ]);
    }
}
