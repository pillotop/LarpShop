<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(): View
    {
        return view('checkout.pages.show');
    }

    public function store(
        Request $request,
        OrderCreationService $orders,
    ): JsonResponse {

        $user = $request->user();

        $order = $orders->create($user);

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
        ], 201);
    }
}
