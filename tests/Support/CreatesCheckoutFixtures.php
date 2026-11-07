<?php

namespace Tests\Support;

use App\Models\Address;
use App\Models\User;

trait CreatesCheckoutFixtures
{
    private function shippingAddress(User $user, array $overrides = []): Address
    {
        return $user->addresses()->create(array_replace([
            'recipient_name' => 'Test recipient',
            'phone_number' => '0901234567',
            'address_line_1' => '123 Test Street',
            'address_line_2' => 'Apartment 4',
            'city' => 'Test City',
            'region' => 'Test Region',
            'postal_code' => '700000',
            'country_code' => 'VN',
            'is_default' => false,
        ], $overrides));
    }
}
