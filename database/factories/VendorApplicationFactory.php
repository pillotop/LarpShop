<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorApplicationFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory()];
    }

    public function complete(): static
    {
        return $this->state(fn () => [
            'seller_type' => 'individual',
            'seller_country_code' => 'VN',
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+84901234567',
            'store_name' => 'Store '.fake()->unique()->uuid(),
            'description' => 'Handmade goods and accessories.',
            'pickup_recipient_name' => fake()->name(),
            'pickup_phone_number' => '+84901234567',
            'pickup_country_code' => 'VN',
            'pickup_address_line_1' => '123 Example Street',
            'pickup_city' => 'Example City',
            'delivery_mode' => 'domestic',
        ]);
    }
}
