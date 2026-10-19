<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'category_id' => 3,
                'vendor_id' => 1,
                'name' => 'Canvas Backpack',
                'slug' => 'canvas-backpack',
                'description' => 'A durable canvas backpack for daily use.',
                'price' => 39.99,
                'stock' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'vendor_id' => 1,
                'name' => 'Travel Mug',
                'slug' => 'travel-mug',
                'description' => 'A reusable mug for coffee and tea.',
                'price' => 14.50,
                'stock' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('products')->insert([
            [
                'category_id' => 13, // Cameras
                'vendor_id'   => 2, // VoltTech Gadgets
                'name'        => 'Ultra HD 4K Action Camera',
                'slug'        => 'ultra-hd-4k-action-camera',
                'description' => 'Capture your adventures in stunning 4K resolution with durable waterproof housing and image stabilization.',
                'price'       => 299.99,
                'stock'       => 45,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 12, // Head phones
                'vendor_id'   => 3, // AudioPulse Hi-Fi
                'name'        => 'Studio Pro Wireless Headphones',
                'slug'        => 'studio-pro-wireless-headphones',
                'description' => 'Active noise cancelling headphones with high-fidelity studio sound and 30-hour battery life.',
                'price'       => 199.99,
                'stock'       => 120,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 7, // Home speakers
                'vendor_id'   => 3, // AudioPulse Hi-Fi
                'name'        => 'AeroSound Bluetooth Bookshelf Speakers',
                'slug'        => 'aerosound-bluetooth-bookshelf-speakers',
                'description' => 'Powerful stereo sound system with seamless Bluetooth connectivity and rich, room-filling bass.',
                'price'       => 249.50,
                'stock'       => 30,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 10, // PC gaming
                'vendor_id'   => 4, // CyberGear Gaming
                'name'        => 'Vortex RGB Mechanical Gaming Keyboard',
                'slug'        => 'vortex-rgb-mechanical-gaming-keyboard',
                'description' => 'Customizable per-key RGB lighting, tactile mechanical switches, and an ergonomic magnetic wrist rest.',
                'price'       => 129.99,
                'stock'       => 85,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 9, // Computer & office
                'vendor_id'   => 5, // NextGen Components
                'name'        => 'Apex UltraWide 34-Inch Curved Monitor',
                'slug'        => 'apex-ultrawide-34-inch-curved-monitor',
                'description' => 'Immersive 144Hz gaming and productivity display with crisp 4K resolution and HDR support.',
                'price'       => 499.00,
                'stock'       => 15,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 8, // Home automation
                'vendor_id'   => 6, // SmartHome Innovations
                'name'        => 'Smart Hub Pro with Voice Control',
                'slug'        => 'smart-hub-pro-with-voice-control',
                'description' => 'Centralized home automation hub to seamlessly manage smart lights, thermostats, and security locks.',
                'price'       => 149.99,
                'stock'       => 60,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        DB::table('product_images')->insert([
            [
                'product_id' => 1,
                'path' => 'products/canvas-backpack.jpg',
                'alt_text' => 'Canvas backpack, front view',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 2,
                'path' => 'products/travel-mug.jpg',
                'alt_text' => 'Reusable travel mug',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
