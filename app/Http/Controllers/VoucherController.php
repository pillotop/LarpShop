<?php

namespace App\Http\Controllers;

use App\Queries\Cart\CartQuery;
use App\Services\Orders\OrderCreationService;
use App\Services\Promotions\VoucherService;
use App\Support\Money\MoneyCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function preview(
        Request $request,
        CartQuery $carts,
        VoucherService $vouchers,
        MoneyCalculator $money,
    ): JsonResponse {
        $validated = $request->validate([
            'voucher_code' => ['required', 'string', 'max:40'],
            'selected_cart_item_ids' => ['nullable', 'array'],
            'selected_cart_item_ids.*' => ['integer'],
            'shipping_method' => ['nullable', 'string', Rule::in(array_keys(OrderCreationService::SHIPPING_FEES))],
        ]);

        $cart = $carts->findForOwner(['user_id' => $request->user()->getKey()]);
        $items = $cart
            ? $cart->items()
                ->with(['itemable', 'variant'])
                ->when(
                    isset($validated['selected_cart_item_ids']),
                    fn ($q) => $q->whereIn('cart_item_id', $validated['selected_cart_item_ids']),
                )
                ->get()
            : collect();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => __('checkout.errors.empty'),
                'errors' => ['voucher_code' => [__('checkout.errors.empty')]],
            ], 422);
        }

        $subtotal = $money->zero();
        $lineSubtotals = [];

        foreach ($items as $item) {
            $product = $item->itemable;
            if (! $product) {
                continue;
            }
            $unitPrice = $item->variant ? $item->variant->price : $product->price;
            $lineSubtotal = $money->subtotal((string) $unitPrice, (int) $item->quantity);
            $lineSubtotals[] = [
                'vendor_id' => $product->vendor_id,
                'subtotal' => $lineSubtotal,
            ];
            $subtotal = $money->add($subtotal, $lineSubtotal);
        }

        $method = $validated['shipping_method'] ?? 'home_delivery';
        $shippingFee = OrderCreationService::SHIPPING_FEES[$method] ?? '0.00';
        $shippingFeeDecimal = $money->of($shippingFee);

        $resolved = $vouchers->preview(
            $request->user(),
            $validated['voucher_code'],
            $lineSubtotals,
            $shippingFeeDecimal,
        );

        $discount = $resolved['discount'];
        $grandTotal = $money->subtract($money->add($subtotal, $shippingFeeDecimal), $discount);

        return response()->json([
            'success' => true,
            'voucher' => [
                'code' => $resolved['voucher']->code,
                'title' => $resolved['voucher']->title,
                'type' => $resolved['voucher']->type,
                'discount_amount' => $money->format($discount),
            ],
            'subtotal_amount' => $money->format($subtotal),
            'shipping_fee' => $shippingFee,
            'grand_total' => $money->format($grandTotal),
            'message' => __('checkout.vouchers.applied', ['code' => $resolved['voucher']->code]),
        ]);
    }
}
