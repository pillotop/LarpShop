<?php

namespace App\Http\Controllers;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Queries\Reviews\ProductReviewQuery;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(
        Request $request,
        Order $order,
        ProductReviewQuery $reviews,
    ): JsonResponse|View {
        $order = $request->user()->orders()->findOrFail($order->getKey());

        $order->load(['details.product.images', 'details.product.vendor', 'details.variant', 'addresses', 'payments']);
        if (! $request->expectsJson()) {
            $productIds = $order->details->pluck('product_id')->unique()->values()->all();
            $reviewableProductIds = $reviews->eligibleProductIds($request->user(), $productIds);

            return view('orders.pages.show', compact('order', 'reviewableProductIds'));
        }

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at,
        ]);
    }

    public function confirmDelivery(
        Request $request,
        Order $order,
    ): RedirectResponse {
        $ownedOrder = $request->user()->orders()->findOrFail($order->getKey());

        DB::transaction(function () use ($ownedOrder) {
            $lockedOrder = Order::query()
                ->whereKey($ownedOrder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::Paid) {
                throw ValidationException::withMessages([
                    'order' => __('orders.errors.cannot_confirm_delivery'),
                ]);
            }

            $shippedDetails = $lockedOrder->details()
                ->where('fulfillment_status', FulfillmentStatus::Shipped->value)
                ->lockForUpdate()
                ->get();

            if ($shippedDetails->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => __('orders.errors.cannot_confirm_delivery'),
                ]);
            }

            $now = now();
            foreach ($shippedDetails as $detail) {
                $detail->fulfillment_status = FulfillmentStatus::Delivered;
                if ($detail->shipped_at === null) {
                    $detail->shipped_at = $now;
                }
                if ($detail->delivered_at === null) {
                    $detail->delivered_at = $now;
                }
                $detail->save();
            }
        }, attempts: 3);

        return redirect()
            ->route('orders.show', ['order' => $ownedOrder])
            ->with('status', __('orders.delivery_confirmed'));
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
            ->with('status', __('orders.cancelled'));
    }
}
