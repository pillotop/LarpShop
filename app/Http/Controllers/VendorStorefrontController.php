<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorStorefrontController extends Controller
{
    /**
     * Display the specified vendor storefront with products.
     */
    public function show(Request $request, int|string $vendor): View
    {
        $vendorModel = Vendor::with('user')
            ->where('vendor_id', $vendor)
            ->firstOrFail();

        // Ensure store is active or approved for public storefront view
        if (! in_array($vendorModel->status, ['active', 'approved'])) {
            abort(404, 'Store is currently unavailable.');
        }

        $query = $vendorModel->products()
            ->approved()
            ->with(['images', 'category', 'reviews'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews');

        // Optional search within vendor store
        if ($search = $request->input('q')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        // Optional filter by category within vendor store
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->orderByDesc('created_at')->orderByDesc('product_id'),
        };

        $products = $query->paginate(12)->withQueryString();

        // Categories present in this vendor's catalog for quick filtering
        $categoryIds = $vendorModel->products()->select('category_id')->distinct()->pluck('category_id');
        $vendorCategories = Category::whereIn('category_id', $categoryIds)->get();

        $productCategories = Category::query()->get();

        return view('vendor.pages.storefront', [
            'vendor' => $vendorModel,
            'products' => $products,
            'vendorCategories' => $vendorCategories,
            'productCategories' => $productCategories,
        ]);
    }
}
