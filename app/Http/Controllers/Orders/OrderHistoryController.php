<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Queries\Reviews\ProductReviewQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(Request $request, ProductReviewQuery $reviews): JsonResponse|View
    {
        $orders = $request
            ->user()
            ->orders()
            ->with([
                'details.product.images',
                'payments',
                'addresses',
            ])
            ->latest()
            ->paginate(10);

        if ($request->expectsJson()) {
            return response()->json($orders);
        }

        $productIds = $orders->getCollection()->flatMap->details->pluck('product_id')->unique()->all();
        $reviewableProductIds = $reviews->eligibleProductIds($request->user(), $productIds);

        return view('orders.pages.index', compact('orders', 'reviewableProductIds'));
    }
}
