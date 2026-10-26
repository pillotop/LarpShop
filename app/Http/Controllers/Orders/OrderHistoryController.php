<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class OrderHistoryController extends Controller
{
    public function index(Request $request): JsonResponse | RedirectResponse
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
            return response()->json([

            ]);
        }

        return redirect()->route('profile');
    }
}
