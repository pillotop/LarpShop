<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Legacy standalone entry point delegated to the idempotent DemoCatalogSeeder.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            DemoUserAndVendorSeeder::class,
            DemoCatalogSeeder::class,
        ]);
    }
}
