<?php

namespace App\Services\Promotions;

use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Support\Money\MoneyCalculator;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    public function __construct(
        private MoneyCalculator $money,
    ) {}

    /**
     * Validate a voucher code against cart/order items and compute the discount without mutating DB state.
     *
     * @param  array<int, array{vendor_id: ?int, subtotal: BigDecimal}>  $lineItems
     * @return array{voucher: Voucher, discount: BigDecimal, eligible_subtotal: BigDecimal}
     */
    public function preview(User $user, string $code, array $lineItems, BigDecimal $shippingFee): array
    {
        return $this->resolve($user, $code, $lineItems, $shippingFee, lock: false);
    }

    /**
     * Lock and reserve a voucher redemption inside an active database transaction.
     *
     * @param  array<int, array{vendor_id: ?int, subtotal: BigDecimal}>  $lineItems
     * @return array{voucher: Voucher, discount: BigDecimal, eligible_subtotal: BigDecimal}
     */
    public function consumeLocked(User $user, string $code, array $lineItems, BigDecimal $shippingFee): array
    {
        $resolved = $this->resolve($user, $code, $lineItems, $shippingFee, lock: true);

        $resolved['voucher']->increment('used_count');

        return $resolved;
    }

    public function recordUsage(Voucher $voucher, User $user, Order $order, BigDecimal $discount): VoucherUsage
    {
        return VoucherUsage::create([
            'voucher_id' => $voucher->getKey(),
            'user_id' => $user->getKey(),
            'order_id' => $order->getKey(),
            'discount_amount' => $this->money->format($discount),
        ]);
    }

    /**
     * Release any voucher usage tied to an order when the order is cancelled or expires.
     */
    public function releaseForOrder(Order $order): void
    {
        if ($order->voucher_id === null) {
            return;
        }

        $usage = VoucherUsage::query()
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->first();

        if (! $usage) {
            return;
        }

        $voucher = Voucher::query()
            ->whereKey($usage->voucher_id)
            ->lockForUpdate()
            ->first();

        if ($voucher && $voucher->used_count > 0) {
            $voucher->decrement('used_count');
        }

        $usage->delete();
    }

    /**
     * @param  array<int, array{vendor_id: ?int, subtotal: BigDecimal}>  $lineItems
     * @return array{voucher: Voucher, discount: BigDecimal, eligible_subtotal: BigDecimal}
     */
    private function resolve(User $user, string $code, array $lineItems, BigDecimal $shippingFee, bool $lock): array
    {
        $normalizedCode = strtoupper(trim($code));

        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.invalid'),
            ]);
        }

        $query = Voucher::query()->with('vendor')->where('code', $normalizedCode);
        if ($lock) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if (! $voucher || ! $voucher->is_active) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.invalid'),
            ]);
        }

        $now = now();
        if ($voucher->starts_at !== null && $voucher->starts_at->greaterThan($now)) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.not_started'),
            ]);
        }

        if ($voucher->expires_at !== null && $voucher->expires_at->lessThan($now)) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.expired'),
            ]);
        }

        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.exhausted'),
            ]);
        }

        $userUsageCount = VoucherUsage::query()
            ->where('voucher_id', $voucher->getKey())
            ->where('user_id', $user->getKey())
            ->count();

        if ($voucher->per_user_limit > 0 && $userUsageCount >= $voucher->per_user_limit) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.user_limit'),
            ]);
        }

        $eligibleSubtotal = $this->money->zero();
        foreach ($lineItems as $line) {
            if ($voucher->vendor_id === null || (int) $line['vendor_id'] === (int) $voucher->vendor_id) {
                $eligibleSubtotal = $this->money->add($eligibleSubtotal, $line['subtotal']);
            }
        }

        if ($voucher->vendor_id !== null && $eligibleSubtotal->isZero()) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.vendor_mismatch', [
                    'store' => $voucher->vendor?->store_name ?? __('products.detail.partner'),
                ]),
            ]);
        }

        $minOrder = $this->money->of((string) $voucher->min_order_amount);
        if ($eligibleSubtotal->isLessThan($minOrder)) {
            throw ValidationException::withMessages([
                'voucher_code' => __('checkout.vouchers.min_order', [
                    'amount' => '$'.number_format((float) $voucher->min_order_amount, 2),
                ]),
            ]);
        }

        $discount = match ($voucher->type) {
            Voucher::TYPE_PERCENT => $this->computePercentDiscount($voucher, $eligibleSubtotal),
            Voucher::TYPE_FIXED => $this->money->min($eligibleSubtotal, $this->money->of((string) $voucher->value)),
            Voucher::TYPE_FREE_SHIPPING => $shippingFee,
            default => $this->money->zero(),
        };

        return [
            'voucher' => $voucher,
            'discount' => $discount,
            'eligible_subtotal' => $eligibleSubtotal,
        ];
    }

    private function computePercentDiscount(Voucher $voucher, BigDecimal $eligibleSubtotal): BigDecimal
    {
        $raw = $this->money->percentage($eligibleSubtotal, (string) $voucher->value);

        if ($voucher->max_discount_amount !== null) {
            $raw = $this->money->min($raw, $this->money->of((string) $voucher->max_discount_amount));
        }

        return $this->money->min($eligibleSubtotal, $raw);
    }
}
