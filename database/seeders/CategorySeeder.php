<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronics is a scientific and engineering discipline that studies and applies the principles of physics to design, create, and operate devices that manipulate electrons and other electrically charged particles.',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Useful everyday accessories.',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home & Living',
                'slug' => 'home-and-living',
                'description' => 'Everything you need to make your house a home, from decor to kitchenware.',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty-and-personal-care',
                'description' => 'Essential skincare, cosmetics, haircare, and daily grooming products.',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arts, Crafts & Sewing',
                'slug' => 'arts-crafts-and-sewing',
                'description' => 'A wide variety of crafting materials, sewing tools, yarns, and creative supplies.',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('categories')->insert([
            [
                'name' => 'TVs',
                'slug' => 'tv',
                'description' => 'Television is a telecommunication medium for transmitting moving images and sound',
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home speakers',
                'slug' => 'home-speakers',
                'description' => 'A loudspeaker is a combination of one or more speaker drivers, an enclosure, and electrical connections. The speaker driver is an electroacoustic transducer that converts an electrical audio signal into a corresponding sound.',
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home automation',
                'slug' => 'home-automation',
                'description' => 'Home automation or domotics is building automation for a home. A home automation system will monitor and/or control home attributes such as lighting, climate, entertainment systems, and appliances.',
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Computer & office',
                'slug' => 'computers-laptops-tablets',
                'description' => 'A computer is a machine that can be programmed to automatically carry out sequences of arithmetic or logical operations.',
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'PC gaming',
                'slug' => 'pc-gaming',
                'description' => 'PC gaming, simply put, refers to playing video games on a personal computer, duh! Unlike consoles, which are dedicated gaming machines with fixed hardware configurations, PCs offer unparalleled flexibility and customization options.',
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Video games',
                'slug' => 'video-games',
                'description' => 'A video game is an electronic game that involves interaction with a user interface or input device to generate visual feedback on a display, such as a television or computer screen.',
                'parent_id' => 1,
                'created_at' => now(),
                'updated-at' => now(),
            ],
            [
                'name' => 'Head phones',
                'slug' => 'head-phones',
                'description' => "Headphones are a pair of small loudspeaker drivers worn on or around the head over a user's ears. They are electroacoustic transducers, which convert an electrical signal to a corresponding sound.",
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cameras',
                'slug' => 'cameras',
                'description' => 'A camera is an instrument used to capture and store images and videos, either digitally via an electronic image sensor, or chemically via a light-sensitive material such as photographic film.',
                'parent_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('categories')->insert([
            [
                'name' => 'Backpacks',
                'slug' => 'backpacks',
                'description' => 'Backpacks for school, work, and travel.',
                'parent_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 2,
                'name' => 'Belts & Wallets',
                'slug' => 'belts-wallets',
                'description' => 'Genuine leather belts and sleek RFID-blocking wallets.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 2,
                'name' => 'Hats & Caps',
                'slug' => 'hats-caps',
                'description' => 'Trendy snapbacks, beanies, and sun hats for all seasons.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 2,
                'name' => 'Sunglasses',
                'slug' => 'sunglasses',
                'description' => 'UV-protected polarized eyewear and fashion frames.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 2,
                'name' => 'Jewelry & Watches',
                'slug' => 'jewelry-watches',
                'description' => 'Minimalist watches, rings, bracelets, and necklaces.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('categories')->insert([
            [
                'parent_id' => 3,
                'name' => 'Kitchen & Dining',
                'slug' => 'kitchen-and-dining',
                'description' => 'Cookware, dinnerware, utensils, and gadgets for your kitchen.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 3,
                'name' => 'Home Decor',
                'slug' => 'home-decor',
                'description' => 'Wall art, vases, candles, and decorative accents to style your space.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 3,
                'name' => 'Bedding & Linens',
                'slug' => 'bedding-and-linens',
                'description' => 'Comfortable sheets, duvet covers, pillows, and cozy blankets.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 3,
                'name' => 'Bath',
                'slug' => 'bath',
                'description' => 'Plush towels, shower curtains, bath mats, and bathroom organizers.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'parent_id' => 3,
                'name' => 'Lighting',
                'slug' => 'lighting',
                'description' => 'Table lamps, floor lamps, string lights, and stylish fixtures.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('categories')->insert([
            // Target Category: Makeup
            [
                'parent_id' => 4,
                'name' => 'Makeup',
                'slug' => 'makeup',
                'description' => 'High-quality cosmetics for face, eyes, lips, and skincare-infused beauty.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 1
            [
                'parent_id' => 4,
                'name' => 'Skincare',
                'slug' => 'skincare',
                'description' => 'Cleansers, moisturizers, serums, and treatments for radiant, healthy skin.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 2
            [
                'parent_id' => 4,
                'name' => 'Hair Care',
                'slug' => 'hair-care',
                'description' => 'Shampoos, conditioners, styling products, and hair treatments.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 3
            [
                'parent_id' => 4,
                'name' => 'Fragrance',
                'slug' => 'fragrance',
                'description' => 'Perfumes, colognes, body mists, and essential oil scents.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 4
            [
                'parent_id' => 4,
                'name' => 'Bath & Body',
                'slug' => 'bath-and-body',
                'description' => 'Body washes, lotions, scrubs, and relaxing bath products.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('categories')->insert([
            // Target Category: Fabric
            [
                'parent_id' => 5,
                'name' => 'Fabric',
                'slug' => 'fabric',
                'description' => 'Quality textiles, cotton, fleece, canvas, and specialty fabrics for sewing, quilting, and craft projects.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 1
            [
                'parent_id' => 5,
                'name' => 'Sewing & Needlework',
                'slug' => 'sewing-and-needlework',
                'description' => 'Thread, needles, zippers, buttons, and essential tools for tailoring and garment construction.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 2
            [
                'parent_id' => 5,
                'name' => 'Yarn & Knitting',
                'slug' => 'yarn-and-knitting',
                'description' => 'Soft yarns, knitting needles, crochet hooks, and patterns for cozy handmade creations.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 3
            [
                'parent_id' => 5,
                'name' => 'Patterns',
                'slug' => 'patterns',
                'description' => 'Sewing patterns for apparel, home decor, quilting, and crafts of all skill levels.',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sibling 4
            [
                'parent_id' => 5,
                'name' => 'Craft Supplies & Tools',
                'slug' => 'craft-supplies-and-tools',
                'description' => 'Glues, paints, cutting mats, rotary cutters, and accessories for all your DIY projects.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
