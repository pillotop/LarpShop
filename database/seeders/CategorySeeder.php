<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical parent and child category hierarchy idempotently by slug.
 */
class CategorySeeder extends Seeder
{
    /**
     * @return array<string, array{parent: array<string, mixed>, children: array<int, array<string, string>>}>
     */
    public static function structure(): array
    {
        return [
            'sports-and-outdoors' => [
                'parent' => [
                    'name' => 'Sports & Outdoors',
                    'slug' => 'sports-and-outdoors',
                    'description' => 'Equipment, apparel, and gear for fitness, athletics, and outdoor exploration.',
                ],
                'children' => [
                    ['name' => 'Fitness Equipment', 'slug' => 'fitness-equipment', 'description' => 'Weights, resistance bands, and home gym gear.'],
                    ['name' => 'Cycling', 'slug' => 'cycling', 'description' => 'Mountain bikes, road bikes, and cycling accessories.'],
                    ['name' => 'Outdoor Recreation', 'slug' => 'outdoor-recreation', 'description' => 'Camping gear, tents, and hiking equipment.'],
                    ['name' => 'Team Sports', 'slug' => 'team-sports', 'description' => 'Footballs, basketballs, and training accessories.'],
                    ['name' => 'Activewear', 'slug' => 'activewear', 'description' => 'Moisture-wicking jackets, shirts, and athletic clothing.'],
                ],
            ],
            'electronics' => [
                'parent' => [
                    'name' => 'Electronics',
                    'slug' => 'electronics',
                    'description' => 'Cutting-edge gadgets, home entertainment, and computing devices.',
                ],
                'children' => [
                    ['name' => 'TVs', 'slug' => 'tvs', 'description' => 'High-resolution televisions and smart screens.'],
                    ['name' => 'Home speakers', 'slug' => 'home-speakers', 'description' => 'Wireless and bookshelf sound systems.'],
                    ['name' => 'Home automation', 'slug' => 'home-automation', 'description' => 'Smart lights, hubs, and home security.'],
                    ['name' => 'Computer & office', 'slug' => 'computer-and-office', 'description' => 'Monitors, laptops, and workspace gear.'],
                    ['name' => 'PC gaming', 'slug' => 'pc-gaming', 'description' => 'Mechanical keyboards, gaming mice, and components.'],
                    ['name' => 'Video games', 'slug' => 'video-games', 'description' => 'Electronic games and interaction interfaces.'],
                    ['name' => 'Head phones', 'slug' => 'head-phones', 'description' => 'Electroacoustic transducers worn on ears.'],
                    ['name' => 'Cameras', 'slug' => 'cameras', 'description' => 'Digital and film image capture instruments.'],
                ],
            ],
            'accessories' => [
                'parent' => [
                    'name' => 'Accessories',
                    'slug' => 'accessories',
                    'description' => 'Useful everyday accessories.',
                ],
                'children' => [
                    ['name' => 'Bags & Backpacks', 'slug' => 'bags-backpacks', 'description' => 'Stylish and functional bags for daily commute, travel, and casual use.'],
                    ['name' => 'Belts & Wallets', 'slug' => 'belts-wallets', 'description' => 'Genuine leather belts and sleek RFID-blocking wallets.'],
                    ['name' => 'Hats & Caps', 'slug' => 'hats-caps', 'description' => 'Trendy snapbacks, beanies, and sun hats for all seasons.'],
                    ['name' => 'Sunglasses', 'slug' => 'sunglasses', 'description' => 'UV-protected polarized eyewear and fashion frames.'],
                    ['name' => 'Jewelry & Watches', 'slug' => 'jewelry-watches', 'description' => 'Minimalist watches, rings, bracelets, and necklaces.'],
                ],
            ],
            'home-and-living' => [
                'parent' => [
                    'name' => 'Home & Living',
                    'slug' => 'home-and-living',
                    'description' => 'Everything you need to make your house a home, from decor to kitchenware.',
                ],
                'children' => [
                    ['name' => 'Kitchenware', 'slug' => 'kitchenware', 'description' => 'Essential pots, pans, cutlery, and specialized tools for cooking.'],
                    ['name' => 'Kitchen & Dining', 'slug' => 'kitchen-and-dining', 'description' => 'Cookware, dinnerware, utensils, and gadgets.'],
                    ['name' => 'Home Decor', 'slug' => 'home-decor', 'description' => 'Wall art, vases, candles, and decorative accents.'],
                    ['name' => 'Bedding & Linens', 'slug' => 'bedding-and-linens', 'description' => 'Comfortable sheets, duvet covers, and cozy blankets.'],
                    ['name' => 'Bath', 'slug' => 'bath', 'description' => 'Plush towels, shower curtains, and organizers.'],
                    ['name' => 'Lighting', 'slug' => 'lighting', 'description' => 'Table lamps, floor lamps, and stylish fixtures.'],
                    ['name' => 'Furniture', 'slug' => 'furniture', 'description' => 'Seating, tables, and storage units.'],
                ],
            ],
            'beauty-and-personal-care' => [
                'parent' => [
                    'name' => 'Beauty & Personal Care',
                    'slug' => 'beauty-and-personal-care',
                    'description' => 'Essential skincare, cosmetics, haircare, and daily grooming products.',
                ],
                'children' => [
                    ['name' => 'Makeup', 'slug' => 'makeup', 'description' => 'High-quality cosmetics for face, eyes, lips, and skincare-infused beauty.'],
                    ['name' => 'Skincare', 'slug' => 'skincare', 'description' => 'Cleansers, moisturizers, serums, and treatments.'],
                    ['name' => 'Hair Care', 'slug' => 'hair-care', 'description' => 'Shampoos, conditioners, styling products, and treatments.'],
                    ['name' => 'Fragrance', 'slug' => 'fragrance', 'description' => 'Perfumes, colognes, and body mists.'],
                    ['name' => 'Bath & Body', 'slug' => 'bath-and-body', 'description' => 'Body washes, lotions, scrubs, and relaxing bath products.'],
                ],
            ],
            'arts-crafts-and-sewing' => [
                'parent' => [
                    'name' => 'Arts, Crafts & Sewing',
                    'slug' => 'arts-crafts-and-sewing',
                    'description' => 'A wide variety of crafting materials, sewing tools, yarns, and supplies.',
                ],
                'children' => [
                    ['name' => 'Fabric', 'slug' => 'fabric', 'description' => 'Quality textiles, cotton, fleece, canvas, and specialty fabrics.'],
                    ['name' => 'Sewing & Needlework', 'slug' => 'sewing-and-needlework', 'description' => 'Thread, needles, zippers, and buttons for tailoring.'],
                    ['name' => 'Yarn & Knitting', 'slug' => 'yarn-and-knitting', 'description' => 'Soft yarns, knitting needles, and crochet hooks.'],
                    ['name' => 'Patterns', 'slug' => 'patterns', 'description' => 'Sewing patterns for apparel, home decor, and crafts.'],
                    ['name' => 'Craft Supplies & Tools', 'slug' => 'craft-supplies-and-tools', 'description' => 'Glues, paints, cutting mats, and rotary cutters.'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::structure() as $group) {
            $parent = Category::updateOrCreate(
                ['slug' => $group['parent']['slug']],
                [
                    'name' => $group['parent']['name'],
                    'description' => $group['parent']['description'],
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );

            foreach ($group['children'] as $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'description' => $child['description'],
                        'parent_id' => $parent->category_id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
