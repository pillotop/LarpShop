<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. CATEGORIES (Parents & Children)
        // ==========================================
        $categoryStructure = [
            'sports-and-outdoors' => [
                'parent' => ['name' => 'Sports & Outdoors', 'slug' => 'sports-and-outdoors', 'description' => 'Equipment, apparel, and gear for fitness, athletics, and outdoor exploration.', 'parent_id' => null],
                'children' => [
                    ['name' => 'Fitness Equipment', 'slug' => 'fitness-equipment', 'description' => 'Weights, resistance bands, and home gym gear.'],
                    ['name' => 'Cycling', 'slug' => 'cycling', 'description' => 'Mountain bikes, road bikes, and cycling accessories.'],
                    ['name' => 'Outdoor Recreation', 'slug' => 'outdoor-recreation', 'description' => 'Camping gear, tents, and hiking equipment.'],
                    ['name' => 'Team Sports', 'slug' => 'team-sports', 'description' => 'Footballs, basketballs, and training accessories.'],
                    ['name' => 'Activewear', 'slug' => 'activewear', 'description' => 'Moisture-wicking jackets, shirts, and athletic clothing.'],
                ],
            ],
            'electronics' => [
                'parent' => ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Cutting-edge gadgets, home entertainment, and computing devices.'],
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
                'parent' => ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Useful everyday accessories.'],
                'children' => [
                    ['name' => 'Bags & Backpacks', 'slug' => 'bags-backpacks', 'description' => 'Stylish and functional bags for daily commute, travel, and casual use.'],
                    ['name' => 'Belts & Wallets', 'slug' => 'belts-wallets', 'description' => 'Genuine leather belts and sleek RFID-blocking wallets.'],
                    ['name' => 'Hats & Caps', 'slug' => 'hats-caps', 'description' => 'Trendy snapbacks, beanies, and sun hats for all seasons.'],
                    ['name' => 'Sunglasses', 'slug' => 'sunglasses', 'description' => 'UV-protected polarized eyewear and fashion frames.'],
                    ['name' => 'Jewelry & Watches', 'slug' => 'jewelry-watches', 'description' => 'Minimalist watches, rings, bracelets, and necklaces.'],
                ],
            ],
            'home-and-living' => [
                'parent' => ['name' => 'Home & Living', 'slug' => 'home-and-living', 'description' => 'Everything you need to make your house a home, from decor to kitchenware.'],
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
                'parent' => ['name' => 'Beauty & Personal Care', 'slug' => 'beauty-and-personal-care', 'description' => 'Essential skincare, cosmetics, haircare, and daily grooming products.'],
                'children' => [
                    ['name' => 'Makeup', 'slug' => 'makeup', 'description' => 'High-quality cosmetics for face, eyes, lips, and skincare-infused beauty.'],
                    ['name' => 'Skincare', 'slug' => 'skincare', 'description' => 'Cleansers, moisturizers, serums, and treatments.'],
                    ['name' => 'Hair Care', 'slug' => 'hair-care', 'description' => 'Shampoos, conditioners, styling products, and treatments.'],
                    ['name' => 'Fragrance', 'slug' => 'fragrance', 'description' => 'Perfumes, colognes, and body mists.'],
                    ['name' => 'Bath & Body', 'slug' => 'bath-and-body', 'description' => 'Body washes, lotions, scrubs, and relaxing bath products.'],
                ],
            ],
            'arts-crafts-and-sewing' => [
                'parent' => ['name' => 'Arts, Crafts & Sewing', 'slug' => 'arts-crafts-and-sewing', 'description' => 'A wide variety of crafting materials, sewing tools, yarns, and supplies.'],
                'children' => [
                    ['name' => 'Fabric', 'slug' => 'fabric', 'description' => 'Quality textiles, cotton, fleece, canvas, and specialty fabrics.'],
                    ['name' => 'Sewing & Needlework', 'slug' => 'sewing-and-needlework', 'description' => 'Thread, needles, zippers, and buttons for tailoring.'],
                    ['name' => 'Yarn & Knitting', 'slug' => 'yarn-and-knitting', 'description' => 'Soft yarns, knitting needles, and crochet hooks.'],
                    ['name' => 'Patterns', 'slug' => 'patterns', 'description' => 'Sewing patterns for apparel, home decor, and crafts.'],
                    ['name' => 'Craft Supplies & Tools', 'slug' => 'craft-supplies-and-tools', 'description' => 'Glues, paints, cutting mats, and rotary cutters.'],
                ],
            ],
        ];

        $createdCategories = [];

        foreach ($categoryStructure as $groupKey => $data) {
            $parentCat = Category::updateOrCreate(
                ['slug' => $data['parent']['slug']],
                [
                    'name' => $data['parent']['name'],
                    'description' => $data['parent']['description'],
                    'parent_id' => null,
                ]
            );

            foreach ($data['children'] as $child) {
                $createdCategories[$child['slug']] = Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'description' => $child['description'],
                        'parent_id' => $parentCat->category_id,
                    ]
                );
            }
        }

        // ==========================================
        // 2. USERS & VENDORS
        // ==========================================

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@admin.com',
                'password' => Hash::make('password123'),
                'phone_number' => null,
                'role' => 'admin',
            ]
        );

        $customersData = [
            ['username' => 'johndoe', 'email' => 'john@example.com', 'phone_number' => '+15551111', 'role' => 'customer'],
            ['username' => 'janestewart', 'email' => 'jane@example.com', 'phone_number' => '+15552222', 'role' => 'customer'],
            ['username' => 'alexrivera', 'email' => 'alex@example.com', 'phone_number' => '+15553333', 'role' => 'customer'],
            ['username' => 'mariagarcia', 'email' => 'maria@example.com', 'phone_number' => '+15554444', 'role' => 'customer'],
        ];

        foreach ($customersData as $cData) {
            User::updateOrCreate(
                ['username' => $cData['username']],
                [
                    'email' => $cData['email'],
                    'password' => Hash::make('password123'),
                    'phone_number' => $cData['phone_number'],
                    'role' => $cData['role'],
                ]
            );
        }

        $vendorsData = [
            ['username' => 'volttech', 'email' => 'contact@volttech.com', 'store_name' => 'VoltTech Gadgets', 'description' => 'Your ultimate destination for smartphones and gadgets.', 'status' => 'active'],
            ['username' => 'audiopulse', 'email' => 'support@audiopulse.com', 'store_name' => 'AudioPulse Hi-Fi', 'description' => 'High-end audiophile headphones and sound equipment.', 'status' => 'active'],
            ['username' => 'cybergear', 'email' => 'sales@cybergeargaming.com', 'store_name' => 'CyberGear Gaming', 'description' => 'Mechanical keyboards, gaming mice, and custom rig components.', 'status' => 'active'],
            ['username' => 'nextgen', 'email' => 'info@nextgencomponents.com', 'store_name' => 'NextGen Components', 'description' => 'PC parts, graphics cards, and cooling solutions.', 'status' => 'pending'],
            ['username' => 'smarthome', 'email' => 'hello@smarthomeinnovations.com', 'store_name' => 'SmartHome Innovations', 'description' => 'Automated smart lighting and security cameras.', 'status' => 'active'],
            ['username' => 'peakperformance', 'email' => 'store@peakfit.com', 'store_name' => 'Peak Performance Gear', 'description' => 'Professional-grade weights and home gym essentials.', 'status' => 'active'],
            ['username' => 'trailblazer', 'email' => 'sales@trailblazeroutdoors.com', 'store_name' => 'Trailblazer Outdoors', 'description' => 'Everything you need for camping, hiking, and cycling adventures.', 'status' => 'active'],
        ];

        $createdVendors = [];
        $allReviewUsers = User::all();
        foreach ($vendorsData as $data) {
            $user = User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'email' => $data['email'],
                    'password' => Hash::make('password123'),
                    'role' => 'customer',
                ]
            );

            $createdVendors[$data['username']] = Vendor::updateOrCreate(
                ['store_name' => $data['store_name']],
                [
                    'user_id' => $user->user_id,
                    'description' => $data['description'],
                    'status' => $data['status'],
                ]
            );
        }

        // A second store demonstrates that store ownership is independent of account role.
        Vendor::updateOrCreate(
            ['store_name' => 'VoltTech Workshop'],
            [
                'user_id' => $createdVendors['volttech']->user_id,
                'description' => 'A second store awaiting approval for the same owner.',
                'status' => 'pending',
            ]
        );

        // ==========================================
        // 3. PRODUCTS, IMAGES & REVIEWS
        // ==========================================
        $productsData = [
            [
                'vendor' => 'peakperformance',
                'category' => 'fitness-equipment',
                'name' => 'Adjustable Dumbbell Set (24kg)',
                'price' => 299.99,
                'stock' => 40,
                'description' => 'Space-saving adjustable dumbbells with non-slip handles and secure storage tray.',
                'images' => [
                    [
                        'path' => 'https://img.aosomcdn.com/thumbnail/100/n0/product/2026/08/21/AWk9911a021bd4c88.jpg',
                        'alt_text' => 'Adjustable Dumbbell Set - Main View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Saves so much space in my home gym. Weight switching is fast and smooth.'],
                ],
            ],
            [
                'vendor' => 'trailblazer',
                'category' => 'cycling',
                'name' => 'All-Terrain Mountain Bike',
                'price' => 649.00,
                'stock' => 12,
                'description' => 'Durable frame with responsive suspension built for rugged trail riding and outdoor terrain.',
                'images' => [
                    [
                        'path' => 'https://arkansasoutside.com/wp-content/uploads/2023/03/ozarktrail-biker-in-curve-frontside.jpg',
                        'alt_text' => 'All-Terrain Mountain Bike - Action View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Handled rocky mountain tracks like an absolute champ!'],
                    ['rating' => 4, 'comment' => 'Great value for the price tag.'],
                ],
            ],
            [
                'vendor' => 'trailblazer',
                'category' => 'outdoor-recreation',
                'name' => '4-Person Weatherproof Camping Tent',
                'price' => 219.99,
                'stock' => 25,
                'description' => 'Spacious dome tent featuring waterproof flysheets and fast, easy setup for weekend camping.',
                'images' => [
                    [
                        'path' => 'https://bettertrail.com/image/camper-walking-away-from-the-north-face-wawona-tent?hash=%2F4f8b939880f042eb9b2ddfa09a361cf0%2F1ce1681d36fc45dfbfb02c9dd47d6ca6&width=768&quality=70&format=auto&fit=scale-down',
                        'alt_text' => '4-Person Weatherproof Camping Tent - Campsite View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Stood strong through a heavy thunderstorm without a single leak!'],
                ],
            ],
            [
                'vendor' => 'peakperformance',
                'category' => 'team-sports',
                'name' => 'Official Composite Leather Basketball',
                'price' => 39.99,
                'stock' => 100,
                'description' => 'High-grip composite leather indoor/outdoor basketball with exceptional bounce and durability.',
                'images' => [
                    [
                        'path' => 'https://images.squarespace-cdn.com/content/v1/53154ab7e4b0d7a9a7218ac2/1420725527356-MT52GRCG7YLQPBCR4AAX/Fairport+Boys+Varsity+Basketball+team+composite+photo',
                        'alt_text' => 'Official Composite Leather Basketball - Studio View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 4, 'comment' => 'Nice grip out of the box, feels great on outdoor courts.'],
                ],
            ],
            [
                'vendor' => 'peakperformance',
                'category' => 'activewear',
                'name' => 'Lightweight Moisture-Wicking Running Jacket',
                'price' => 69.99,
                'stock' => 50,
                'description' => 'Breathable zipped activewear jacket designed for cool morning runs and outdoor training.',
                'images' => [
                    [
                        'path' => 'https://i5.walmartimages.com/seo/yillto-Plus-Size-Jackets-for-Womens-Long-Sleeve-Lightweight-Shirts-Summer-Hiking-Running-Activewear-Jacket_fb8107cb-c66b-4db0-b25c-6549ceedf638.2ed45c2f0a761fb84d519897784f825a.jpeg?odnHeight=768&odnWidth=768&odnBg=FFFFFF',
                        'alt_text' => 'Lightweight Moisture-Wicking Running Jacket - Product View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Super lightweight and keeps me dry during intense runs.'],
                ],
            ],
            [
                'vendor' => 'volttech',
                'category' => 'tvs',
                'name' => 'Ultra HD 4K Smart TV 55-Inch',
                'price' => 449.99,
                'stock' => 25,
                'description' => 'Crystal clear 4K resolution with built-in streaming apps and vivid color technology.',
                'images' => [
                    [
                        'path' => 'https://www.lg.com/ae/images/tvs/md06140657/gallery/medium01_V2.jpg',
                        'alt_text' => 'Ultra HD 4K Smart TV - Main View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Absolute game changer for movie nights. Stunning picture quality!'],
                    ['rating' => 4, 'comment' => 'Great TV overall, smart features are a bit snappy.'],
                ],
            ],
            [
                'vendor' => 'audiopulse',
                'category' => 'home-speakers',
                'name' => 'AeroSound Bluetooth Bookshelf Speakers',
                'price' => 249.50,
                'stock' => 30,
                'description' => 'Powerful stereo sound system with seamless Bluetooth connectivity and rich bass.',
                'images' => [
                    [
                        'path' => 'https://destiny-files.com/image/webp_large/BBA6624_13.webp',
                        'alt_text' => 'AeroSound Bluetooth Bookshelf Speakers - Main View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Crystal clear highs and deep bass. Incredible for the size.'],
                ],
            ],
            [
                'vendor' => 'cybergear',
                'category' => 'pc-gaming',
                'name' => 'Vortex RGB Mechanical Gaming Keyboard',
                'price' => 129.99,
                'stock' => 85,
                'description' => 'Customizable per-key RGB lighting, tactile mechanical switches, and magnetic wrist rest.',
                'images' => [
                    [
                        'path' => 'https://vortexgear.store/cdn/shop/products/DSC_3227.png?v=1664776562&width=1946',
                        'alt_text' => 'Vortex RGB Mechanical Gaming Keyboard - Main View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'The tactile switches feel amazing for both typing and gaming!'],
                    ['rating' => 4, 'comment' => 'RGB software is okay, but the keyboard itself is top-tier.'],
                ],
            ],
            [
                'vendor' => 'nextgen',
                'category' => 'computer-and-office',
                'name' => 'Apex UltraWide 34-Inch Curved Monitor',
                'price' => 499.00,
                'stock' => 15,
                'description' => 'Immersive 144Hz gaming and productivity display with crisp 4K resolution.',
                'images' => [
                    [
                        'path' => 'https://epicgamers.gg/wp-content/uploads/2024/08/Apex-1.webp',
                        'alt_text' => 'Apex UltraWide 34-Inch Curved Monitor - Main View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 5, 'comment' => 'Multitasking on this curve monitor is a dream come true.'],
                ],
            ],
            [
                'vendor' => 'smarthome',
                'category' => 'home-automation',
                'name' => 'Smart Hub Pro with Voice Control',
                'price' => 149.99,
                'stock' => 60,
                'description' => 'Centralized home automation hub to seamlessly manage smart lights and security.',
                'images' => [
                    [
                        'path' => 'https://www.smarthome.com.au/wp-content/uploads/2023/04/homey-pro-2-700x700.jpg',
                        'alt_text' => 'Smart Hub Pro with Voice Control - Main View',
                    ],
                ],
                'reviews' => [
                    ['rating' => 4, 'comment' => 'Connected all my smart home devices instantly. Very responsive.'],
                    ['rating' => 5, 'comment' => 'Voice control picks up commands from across the room easily!'],
                ],
            ],
        ];

        foreach ($productsData as $pData) {
            $vendor = $createdVendors[$pData['vendor']];
            $category = $createdCategories[$pData['category']];

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($pData['name'])],
                [
                    'category_id' => $category->category_id,
                    'vendor_id' => $vendor->vendor_id,
                    'name' => $pData['name'],
                    'description' => $pData['description'],
                    'price' => $pData['price'],
                    'stock' => $pData['stock'],
                ]
            );

            // Seed Product Images
            if (! empty($pData['images'])) {
                foreach ($pData['images'] as $index => $imageData) {
                    ProductImage::updateOrCreate(
                        [
                            'product_id' => $product->product_id,
                            'sort_order' => $index,
                        ],
                        [
                            'path' => $imageData['path'],
                            'alt_text' => $imageData['alt_text'] ?? $product->name,
                        ]
                    );
                }
            }

            // Seed Product Reviews (feeding your rating container)
            if (! empty($pData['reviews'])) {
                foreach ($pData['reviews'] as $reviewData) {
                    // Pick a random user from our created users list to leave the review
                    $randomUser = $allReviewUsers->random();

                    ProductReview::updateOrCreate(
                        [
                            'product_id' => $product->product_id,
                            'comment' => $reviewData['comment'],
                        ],
                        [
                            'user_id' => $randomUser->user_id,
                            'rating' => $reviewData['rating'],
                        ]
                    );
                }
            }
        }

        // ==========================================
        // 4. SAMPLE ORDERS (UC-44 Investigation)
        // ==========================================
        if (Order::count() === 0) {
            $john = User::where('username', 'johndoe')->first();
            $jane = User::where('username', 'janestewart')->first();
            $seededProducts = Product::orderBy('product_id')->take(3)->get();

            if ($john && $seededProducts->count() >= 2) {
                $first = $seededProducts[0];
                $second = $seededProducts[1];
                $total = ($first->price * 1) + ($second->price * 2);

                $order1 = Order::create([
                    'user_id' => $john->user_id,
                    'total_amount' => $total,
                    'status' => OrderStatus::Pending,
                    'expires_at' => now()->addMinutes(30),
                ]);
                $order1->details()->create([
                    'product_id' => $first->product_id,
                    'quantity' => 1,
                    'unit_price' => $first->price,
                ]);
                $order1->details()->create([
                    'product_id' => $second->product_id,
                    'quantity' => 2,
                    'unit_price' => $second->price,
                ]);
                $first->decrement('stock', 1);
                $second->decrement('stock', 2);
            }

            if ($jane && $seededProducts->count() >= 3) {
                $third = $seededProducts[2];
                $order2 = Order::create([
                    'user_id' => $jane->user_id,
                    'total_amount' => $third->price,
                    'status' => OrderStatus::Paid,
                ]);
                $order2->details()->create([
                    'product_id' => $third->product_id,
                    'quantity' => 1,
                    'unit_price' => $third->price,
                ]);
                $order2->payments()->create([
                    'provider' => 'fake',
                    'method' => PaymentMethod::Card,
                    'amount' => $order2->total_amount,
                    'currency' => config('payments.currency', 'USD'),
                    'status' => PaymentStatus::Succeeded,
                    'completed_at' => now(),
                ]);
            }
        }
    }
}
