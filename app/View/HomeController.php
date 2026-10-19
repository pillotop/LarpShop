<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Services\Products\ProductRetrievalService;
use Illuminate\View\View;
use App\Models\Category;

class HomeController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService)
    {
    }

    public function index(): View
    {
        $productCategories = [
            [ 'name' => 'Recommended' ],
            [ 'name' => 'Beachwear' ],
            [ 'name' => "Kid's Shoes" ],
            [ 'name' => "Women's Lingerie & Lounge" ],
            [ 'name' => 'Smart Home' ],
            [ 'name' => "Women's Shoes" ],
            [ 'name' => 'Appliances' ],
            [ 'name' => 'Beauty & Personal Care' ],
            [ 'name' => "Kid's Fashion" ],
            [ 'name' => 'Musical Instrument' ],
            [ 'name' => "Men's Shoes" ],
            [ 'name' => 'Electronics' ],
            [ 'name' => "Women's Clothing" ],
            [ 'name' => 'Toys & Games' ],
        ];

        $products = $this->productRetrievalService->getProducts();

        return view('homepage', [
            'productCategories' => Category::query()->get(),
            'products' => $products,
        ]);
    }
}
