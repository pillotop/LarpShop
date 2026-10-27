<?php

namespace App\Queries\Address;

use App\Models\Address;
use App\Models\User;

class AddressQuery
{
    public function forUser(User $user)
    {
        return $user
            ->addresses()
            ->orderByDesc('is_default')
            ->orderBy('address_id')
            ->get();
    }

    public function findOwnedOrFail(
        User $user,
        int $addressId,
    ): Address {
        return $user
            ->addresses()
            ->whereKey($addressId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function getForCheckout(
        User $user,
        int $addressId,
    ): Address {
        return $user
            ->addresses()
            ->whereKey($addressId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
