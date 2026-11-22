<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Legacy standalone entry point delegated to the idempotent DemoUserAndVendorSeeder.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoUserAndVendorSeeder::class);
    }
}
