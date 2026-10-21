<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Products\ProductRetrievalService;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private ProductRetrievalService $product_retrieval_service,
    ) {}

    public function index(): View
    {
        return view('categories.pages.index');
    }

    public function show(string $slug): View
    {
        $category = Category::query()
            ->with('children')
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
