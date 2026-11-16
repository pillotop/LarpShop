<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Queries\Address\AddressQuery;
use App\Queries\Cart\CartQuery;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Promotions\VoucherService;
use App\Support\Carts\CartLock;
use App\Support\Money\MoneyCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    public const SHIPPING_FEES = [
        'home_delivery' => '0.00',
        'pickup' => '0.00',
        'express' => '9.99',
    ];

    public function __construct(
        private InventoryReservationService $inventory,
        private AddressQuery $addresses,
        private CartQuery $carts,
        private CartLock $cartLock,
        private MoneyCalculator $money,
        private VoucherService $vouchers,
    ) {}

    public function create(
        User $user,
        int $addressId,
        ?array $selectedCartItemIds = null,
        string $shippingMethod = 'home_delivery',
        ?string $voucherCode = null,
    ): Order {
        $owner = ['user_id' => $user->user_id];

        return $this->cartLock->forOwner(
            $owner,
            fn () => DB::transaction(
                fn () => $this->createLocked($user, $addressId, $selectedCartItemIds, $shippingMethod, $voucherCode),
                attempts: 3,
            )
        );
    }

    private function createLocked(
        User $user,
        int $addressId,
        ?array $selectedCartItemIds = null,
        string $shippingMethod = 'home_delivery',
        ?string $voucherCode = null,
    ): Order {
        // Lock the cart belonging to this user.
        $cart = $this->carts->getForCheckout($user);

        if (! $cart) {
            throw ValidationException::withMessages([
                'cart' => __('checkout.errors.empty'),
            ]);
        }

        // Lock its items so checkout works with a stable cart.
        $items = $cart->items()
            ->when(
                $selectedCartItemIds !== null,
                fn ($q) => $q->whereIn('cart_item_id', $selectedCartItemIds),
            )
            ->orderBy('cart_item_id')
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('checkout.errors.empty'),
            ]);
        }

        if ($selectedCartItemIds !== null && $items->count() !== count($selectedCartItemIds)) {
            throw ValidationException::withMessages([
                'cart' => __('checkout.errors.invalid_item'),
            ]);
        }

        $productType = (new Product)->getMorphClass();

        // Only products are purchasable for now.
        foreach ($items as $item) {
            if (
                $item->itemable_type !== $productType ||
                $item->quantity < 1
            ) {
                throw ValidationException::withMessages([
                    'cart' => __('checkout.errors.invalid_item'),
                ]);
            }
        }

        // Lock products in a consistent primary-key order.
        $productIds = $items
            ->pluck('itemable_id')
            ->unique()
            ->sort()
            ->values();

        $products = Product::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'cart' => __('checkout.errors.unavailable_products'),
            ]);
        }

        $variantIds = $items
            ->pluck('variant_id')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::query()
                ->whereIn('variant_id', $variantIds)
                ->orderBy('variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('variant_id')
            : collect();

        $total = $this->money->zero();
        $lineSubtotals = [];

        // Validate everything before making changes.
        foreach ($items as $item) {
            $product = $products->get($item->itemable_id);
            $variant = $item->variant_id ? $variants->get($item->variant_id) : null;

            if ($item->variant_id !== null && (! $variant || (int) $variant->product_id !== (int) $product->product_id || ! $variant->is_active)) {
                throw ValidationException::withMessages([
                    'cart' => __('checkout.errors.unavailable_products'),
                ]);
            }

            $availableStock = $variant ? $variant->stock : $product->stock;
            $unitPrice = $variant ? $variant->price : $product->price;

            if ($item->quantity > $availableStock) {
                throw ValidationException::withMessages([
                    'cart' => __('checkout.errors.stock_unavailable', ['name' => $product->name]),
                ]);
            }

            $lineSubtotal = $this->money->subtotal(
                (string) $unitPrice,
                (int) $item->quantity,
            );
            $lineSubtotals[] = [
                'vendor_id' => $product->vendor_id,
                'subtotal' => $lineSubtotal,
            ];

            $total = $this->money->add($total, $lineSubtotal);
        }

        $normalizedShippingMethod = array_key_exists($shippingMethod, self::SHIPPING_FEES)
            ? $shippingMethod
            : 'home_delivery';
        $shippingFee = self::SHIPPING_FEES[$normalizedShippingMethod];
        $shippingFeeDecimal = $this->money->of($shippingFee);

        $appliedVoucher = null;
        $discountDecimal = $this->money->zero();
        if ($voucherCode !== null && trim($voucherCode) !== '') {
            $voucherResult = $this->vouchers->consumeLocked($user, $voucherCode, $lineSubtotals, $shippingFeeDecimal);
            $appliedVoucher = $voucherResult['voucher'];
            $discountDecimal = $voucherResult['discount'];
        }

        $grandTotal = $this->money->subtract(
            $this->money->add($total, $shippingFeeDecimal),
            $discountDecimal,
        );

        // All checks passed: create an order snapshot.

        $address = $this
            ->addresses
            ->getForCheckout($user, $addressId);

        $order = Order::create([
            'user_id' => $user->user_id,
            'total_amount' => $this->money->format($grandTotal),
            'subtotal_amount' => $this->money->format($total),
            'discount_amount' => $this->money->format($discountDecimal),
            'voucher_id' => $appliedVoucher?->getKey(),
            'voucher_code' => $appliedVoucher?->code,
            'status' => OrderStatus::Pending,
            'shipping_method' => $normalizedShippingMethod,
            'shipping_fee' => $shippingFee,
            'expires_at' => now()->addMinutes(30),
        ]);

        if ($appliedVoucher !== null) {
            $this->vouchers->recordUsage($appliedVoucher, $user, $order, $discountDecimal);
        }

        $order->addresses()->create([
            'type' => 'shipping',
            ...$address->snapshot(),
        ]);

        foreach ($items as $item) {
            $product = $products->get($item->itemable_id);
            $variant = $item->variant_id ? $variants->get($item->variant_id) : null;
            $unitPrice = $variant ? $variant->price : $product->price;

            $order->details()->create([
                'product_id' => $product->product_id,
                'variant_id' => $variant?->variant_id,
                'variant_name' => $variant?->name,
                'variant_sku' => $variant?->sku,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
            ]);

            $this->inventory->reserve($product, $item->quantity, $variant);
        }

        // Remove only checked-out items so unselected cart items remain intact.
        $cart->items()
            ->whereIn('cart_item_id', $items->pluck('cart_item_id'))
            ->delete();

        return $order;
    }
}
