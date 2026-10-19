<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vendors')->insert([
            [
                'user_id' => 1,
                'store_name' => 'Everyday Goods',
                'description' => 'Practical products for everyday life.',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'store_name' => 'VoltTech Gadgets',
                'description' => 'Your ultimate destination for the latest smartphones, tablets, and smart home gadgets.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'store_name' => 'AudioPulse Hi-Fi',
                'description' => 'High-end audiophile headphones, wireless Bluetooth speakers, and professional sound equipment.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'store_name' => 'CyberGear Gaming',
                'description' => 'Top-tier mechanical keyboards, precision gaming mice, high-refresh monitors, and custom rig components.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 5,
                'store_name' => 'NextGen Components',
                'description' => 'PC parts, graphics cards, motherboards, and efficient liquid cooling solutions for custom builders.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 6,
                'store_name' => 'SmartHome Innovations',
                'description' => 'Automated smart lighting, 4K security cameras, smart locks, and voice-controlled home hubs.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
