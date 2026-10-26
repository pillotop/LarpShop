<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Queries\Address\AddressQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderAddressService
{
    public function __construct(private AddressQuery $addresses) {}

    public function updateShipping(User $user, Order $order, int $addressId): void
    {
        DB::transaction(function () use ($user, $order, $addressId) {
            $order = $user->orders()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            if ($order->status !== OrderStatus::Pending || $order->expires_at?->lessThanOrEqualTo(now()) ||
                $order->payments()->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Processing->value, PaymentStatus::Succeeded->value, PaymentStatus::RefundRequired->value])->exists()) {
                throw ValidationException::withMessages(['address_id' => 'The shipping address can no longer be changed for this order.']);
            }
            $address = $this->addresses->getForCheckout($user, $addressId);
            $order->addresses()->updateOrCreate(['type' => 'shipping'], $address->snapshot());
        }, attempts: 3);
    }
}
