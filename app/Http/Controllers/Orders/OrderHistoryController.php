<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Queries\Orders\PurchaseHistoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect(route('profile').'#'.PurchaseHistoryQuery::FRAGMENT);
        }

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

        return response()->json($orders);
    }
}
