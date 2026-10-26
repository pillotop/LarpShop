<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(
        Request $request,
        Order $order,
    ): JsonResponse {
        $order = $request->user()->orders()->findOrFail($order->getKey());

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at,
        ]);
    }

    public function cancel(
        Request $request,
        Order $order,
        OrderLifecycleService $lifecycle,
    ): RedirectResponse {
        $order = $request->user()
            ->orders()
            ->findOrFail($order->getKey());

        $lifecycle->cancel($order);

        return redirect()
            ->route('orders.show', ['order' => $order])
            ->with('status', 'Order cancelled');
    }
}
