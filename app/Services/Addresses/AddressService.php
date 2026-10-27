<?php

namespace App\Services\Addresses;

use App\Models\Address;
use App\Models\User;
use App\Queries\Address\AddressQuery;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function __construct(
        private AddressQuery $addresses
    ) {}

    public function create(User $user, array $data) {
        return DB::transaction(function () use ($user, $data) {
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasAddresses = $user->addresses()->exists();

            return $user->addresses()->create([
                ...$data,
                'country_code' => strtoupper($data['country_code']),
                'is_default' => !$hasAddresses
            ]);
        });
    }

    public function update(User $user, Address $address, array $data): Address
    {
        $address = $user
            ->addresses()
            ->findOrFail($address->getKey());

        $address->update([
            ...$data,
            'country_code' => strtoupper($data['country_code']),
        ]);

        return $address->refresh();
    }

    public function setDefault(User $user, Address $address): void
    {
        DB::transaction(function () use ($user, $address) {
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $address = $user->addresses()
                ->whereKey($address->getKey())
                ->firstOrFail();

            $user->addresses()->update([
                'is_default' => false,
            ]);

            $address->update([
                'is_default' => true,
            ]);
        });
    }

    public function delete(User $user, Address $address): void
    {
        DB::transaction(function () use ($user, $address) {
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $address = $user->addresses()
                ->whereKey($address->getKey())
                ->firstOrFail();

            $wasDefault = $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $replacement = $user->addresses()
                    ->orderBy('address_id')
                    ->first();

                $replacement?->update([
                    'is_default' => true,
                ]);
            }
        });
    }
}
