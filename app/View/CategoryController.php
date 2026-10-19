<?php

namespace App\View;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use App\Models\Category;
use App\Services\Products\ProductRetrievalService;

class CategoryController extends Controller
{
    public function __construct(
        private ProductRetrievalService $product_retrieval_service,
    ) {
    }

    public function index(): View
    {
        return view('categories.pages.index');
    }

    public function show(string $slug): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('categories.pages.show', [
            'category' => $category,
            'products' => $this->product_retrieval_service->getProducts(
                categoryId: $category->category_id,
            ),
            'productCategories' => Category::query()->get(),
        ]);
    }
}
