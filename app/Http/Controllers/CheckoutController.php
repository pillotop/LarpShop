<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Models\Order;
use App\Queries\Address\AddressQuery;
use App\Services\Orders\OrderAddressService;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(
        Request $request,
        Order $order,
        AddressQuery $addresses,
    ): View {
        abort_unless(
            (int) $order->user_id === (int) $request->user()->user_id,
            404,
        );

        $order->load(['addresses', 'details.product']);

        $payable =
            $order->status === OrderStatus::Pending &&
            ! $order->expires_at?->lessThanOrEqualTo(now());

        $activePayment = $order->payments()
            ->whereIn('status', [
                PaymentStatus::Pending->value,
                PaymentStatus::Processing->value,
                PaymentStatus::Succeeded->value,
                PaymentStatus::RefundRequired->value,
            ])->exists();

        return view('checkout.pages.show', [
            'order' => $order,
            'user' => $request->user(),
            'addresses' => $addresses->forUser($request->user()),
            'canChangeAddress' => $payable && ! $activePayment,
            'canPay' => $payable && ! $activePayment && $order->addresses->contains('type', 'shipping'),
        ]);
    }

    public function updateAddress(CheckoutRequest $request, Order $order, OrderAddressService $addresses): RedirectResponse
    {
        $addresses->updateShipping(
            $request->user(),
            $order,
            $request->integer('address_id'),
        );

        return redirect()->route('checkout.show', $order)
            ->with('success', 'Shipping address updated.');
    }

    public function store(
        CheckoutRequest $request,
        OrderCreationService $orders,
    ): JsonResponse|RedirectResponse {

        $order = $orders->create(
            $request->user(),
            $request->integer('address_id'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'order_id' => $order->order_id,
                'status' => $order->status->value,
                'total_amount' => $order->total_amount,
            ], 201);
        }

        return redirect()->route('checkout.show', ['order' => $order]);
    }
}
