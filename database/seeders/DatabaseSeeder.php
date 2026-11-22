<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates idempotent LarpShop demo dataset seeding across catalog, stores, transactions, and customer activity.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            DemoUserAndVendorSeeder::class,
            DemoCatalogSeeder::class,
            DemoTransactionSeeder::class,
            DemoCustomerActivitySeeder::class,
            BannerGroupSeeder::class,
            SuppliedBannerSeeder::class,
            PromoSectionSeeder::class,
            BroadSelectSeeder::class,
        ]);
    }
}
