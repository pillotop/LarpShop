<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\DemoSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds saved addresses, wishlists, active carts, and verified-buyer product reviews idempotently.
 */
class DemoCustomerActivitySeeder extends Seeder
{
    public function __construct(private DemoSeedContext $context) {}

    public function run(): void
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->whereDoesntHave('vendors')
            ->orderBy('user_id')
            ->get();

        $catalogProducts = Product::query()
            ->approved()
            ->active()
            ->whereHas('vendor', fn ($q) => $q->active())
            ->where('stock', '>=', 5)
            ->orderBy('product_id')
            ->get();

        if ($customers->isEmpty() || $catalogProducts->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($customers, $catalogProducts): void {
            $this->seedAddresses($customers);
            $this->seedWishlists($customers, $catalogProducts);
            $this->seedCarts($customers, $catalogProducts);
            $this->seedVerifiedReviews();
        });
    }

    private function seedAddresses($customers): void
    {
        $cities = [
            ['Austin', 'TX', '78701', 'US'],
            ['Seattle', 'WA', '98101', 'US'],
            ['Chicago', 'IL', '60601', 'US'],
            ['Denver', 'CO', '80202', 'US'],
            ['Portland', 'OR', '97201', 'US'],
        ];

        foreach ($customers->take(45) as $idx => $user) {
            [$city, $region, $postal, $country] = $cities[$idx % count($cities)];
            $name = ucwords(str_replace(['_', '.'], ' ', $user->username));
            $primaryLine = sprintf('%d Oakridge Ave (Demo)', 200 + $idx * 3);

            Address::updateOrCreate(
                ['user_id' => $user->user_id, 'address_line_1' => $primaryLine],
                [
                    'recipient_name' => $name,
                    'phone_number' => $user->phone_number ?? sprintf('+1555050%04d', $idx + 1),
                    'address_line_2' => 'Apt '.(($idx % 12) + 1),
                    'city' => $city,
                    'region' => $region,
                    'postal_code' => $postal,
                    'country_code' => $country,
                    'is_default' => true,
                ]
            );

            if ($idx % 3 === 0) {
                $secondaryLine = sprintf('%d Riverfront Suite (Demo)', 50 + $idx * 2);
                Address::updateOrCreate(
                    ['user_id' => $user->user_id, 'address_line_1' => $secondaryLine],
                    [
                        'recipient_name' => $name.' (Office)',
                        'phone_number' => $user->phone_number ?? sprintf('+1555050%04d', $idx + 1),
                        'address_line_2' => null,
                        'city' => $city,
                        'region' => $region,
                        'postal_code' => $postal,
                        'country_code' => $country,
                        'is_default' => false,
                    ]
                );
            }
        }
    }

    private function seedWishlists($customers, $products): void
    {
        $poolSize = $products->count();
        foreach ($customers->take(32) as $idx => $user) {
            $ids = [];
            $count = 2 + ($idx % 3);
            for ($k = 0; $k < $count; $k++) {
                $ids[] = $products[($idx * 3 + $k * 5) % $poolSize]->product_id;
            }
            $user->wishlistProducts()->syncWithoutDetaching(array_unique($ids));
        }
    }

    private function seedCarts($customers, $products): void
    {
        $morphClass = (new Product)->getMorphClass();
        $poolSize = $products->count();

        foreach ($customers->take(18) as $idx => $user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->user_id]);
            $itemCount = 1 + ($idx % 3);

            for ($k = 0; $k < $itemCount; $k++) {
                $product = $products[($idx * 4 + $k * 9) % $poolSize];
                if ($product->stock < 1) {
                    continue;
                }

                CartItem::firstOrCreate(
                    [
                        'cart_id' => $cart->cart_id,
                        'itemable_type' => $morphClass,
                        'itemable_id' => $product->product_id,
                    ],
                    [
                        'quantity' => min(1 + (($idx + $k) % 2), $product->stock),
                    ]
                );
            }
        }
    }

    private function seedVerifiedReviews(): void
    {
        $anchor = $this->context->anchor();

        $existingPairs = ProductReview::query()
            ->select(['user_id', 'product_id'])
            ->get()
            ->mapWithKeys(fn ($r) => [$r->user_id.'|'.$r->product_id => true])
            ->all();

        // Gather earliest succeeded payment timestamp per (user_id, product_id) for approved products.
        $eligibleLines = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->join('products', 'products.product_id', '=', 'order_details.product_id')
            ->join('categories', 'categories.category_id', '=', 'products.category_id')
            ->join('payments', function ($join): void {
                $join->on('payments.order_id', '=', 'orders.order_id')
                    ->where('payments.status', PaymentStatus::Succeeded->value)
                    ->whereNotNull('payments.completed_at');
            })
            ->where('orders.status', OrderStatus::Paid->value)
            ->where('products.status', Product::STATUS_APPROVED)
            ->select([
                'orders.user_id',
                'order_details.product_id',
                'products.name as product_name',
                'categories.slug as category_slug',
                DB::raw('MIN(payments.completed_at) as purchased_at'),
            ])
            ->groupBy('orders.user_id', 'order_details.product_id', 'products.name', 'categories.slug')
            ->orderBy('order_details.product_id')
            ->orderBy('orders.user_id')
            ->get();

        $totalReviews = count($existingPairs);

        foreach ($eligibleLines as $idx => $row) {
            if ($totalReviews >= 220) {
                break;
            }

            // Intentionally skip ~20% of products by modulo so some catalog items remain unreviewed.
            if ($row->product_id > 12 && ($row->product_id % 5 === 0)) {
                continue;
            }

            $pairKey = $row->user_id.'|'.$row->product_id;
            if (isset($existingPairs[$pairKey])) {
                continue;
            }
            $existingPairs[$pairKey] = true;

            $purchasedAt = CarbonImmutable::parse($row->purchased_at);
            $rating = $this->ratingForItem((int) $row->product_id, $idx, $purchasedAt, $anchor);
            $comment = $this->commentForItem($row->product_name, $row->category_slug, $rating, $idx);

            $reviewAt = $purchasedAt->addHours(24 + (($idx * 17) % 96));
            if ($reviewAt->greaterThan($anchor)) {
                $reviewAt = $anchor->subMinutes(15);
            }
            if ($reviewAt->lessThanOrEqualTo($purchasedAt)) {
                $reviewAt = $purchasedAt->addMinutes(10);
            }

            $review = new ProductReview;
            $review->user_id = (int) $row->user_id;
            $review->product_id = (int) $row->product_id;
            $review->rating = $rating;
            $review->comment = $comment;
            $review->created_at = $reviewAt;
            $review->updated_at = $reviewAt;
            $review->save();

            $totalReviews++;
        }
    }

    private function ratingForItem(int $productId, int $idx, CarbonImmutable $purchasedAt, CarbonImmutable $anchor): int
    {
        // Include varied recent 1-2 star feedback for low-rating quality reports.
        if ($purchasedAt->greaterThanOrEqualTo($anchor->subDays(28)) && $idx % 13 === 0) {
            return ($idx % 2) + 1;
        }

        // Vary distribution per product so ratings are organic rather than identical across items.
        $roll = ($productId * 7 + $idx * 3) % 20;

        return match (true) {
            $roll <= 9 => 5,
            $roll <= 15 => 4,
            $roll <= 17 => 3,
            $roll === 18 => 2,
            default => 1,
        };
    }

    private function commentForItem(string $productName, string $categorySlug, int $rating, int $idx): string
    {
        $domainHint = match (true) {
            str_contains($categorySlug, 'tv') || str_contains($categorySlug, 'camera') || str_contains($categorySlug, 'speaker') || str_contains($categorySlug, 'head') || str_contains($categorySlug, 'gaming') || str_contains($categorySlug, 'computer') || str_contains($categorySlug, 'automation') => [
                5 => ['Crisp build and responsive performance right out of the box.', 'Setup took minutes and daily reliability has been solid.'],
                4 => ['Works smoothly overall; cable length could be slightly longer.', 'Very good hardware value compared with similar studio models.'],
                3 => ['Meets basic expectations, though firmware setup felt a bit slow.', 'Decent performance for the price point, nothing extraordinary.'],
                2 => ['Intermittent pairing drop noticed after a few days of use.', 'Packaging arrived scuffed and the buttons feel looser than expected.'],
                1 => ['Stopped powering on reliably after the first week.', 'Compatibility fell short of what the product listing implied.'],
            ],
            str_contains($categorySlug, 'kitchen') || str_contains($categorySlug, 'decor') || str_contains($categorySlug, 'bedding') || str_contains($categorySlug, 'bath') || str_contains($categorySlug, 'furniture') || str_contains($categorySlug, 'lighting') => [
                5 => ['Sturdy craftsmanship and fits nicely with our living space.', 'Heavy-duty finish that cleans up effortlessly after daily use.'],
                4 => ['Looks great in person; assembly hardware instructions were clear.', 'Warm finish and solid weight for everyday household use.'],
                3 => ['Usable everyday piece, though color tone is slightly warmer than photos.', 'Acceptable quality overall, delivery box needed better padding.'],
                2 => ['Noticeable surface scuffing along one edge out of the box.', 'Lighter gauge material than anticipated from the specs.'],
                1 => ['Arrived with a loose joint and had to request support.', 'Does not sit level on tile flooring.'],
            ],
            default => [
                5 => ['Thoughtful material quality and exactly matched the variant description.', 'Comfortable daily staple; seams and finish held up great.'],
                4 => ['True to description with reliable shipping speed.', 'Good everyday value; would order another colorway next time.'],
                3 => ['Fine overall quality, sizing runs slightly snug.', 'Works as described though nothing stands out over similar picks.'],
                2 => ['Texture felt rougher than expected after unboxing.', 'Minor stitching flaw along the side pocket.'],
                1 => ['Item shade did not match the variant label I picked.', 'Material wore down much faster than expected.'],
            ],
        };

        $choices = $domainHint[$rating];
        $sentence = $choices[$idx % count($choices)];

        return sprintf('[Demo Review] %s (%s)', $sentence, $productName);
    }
}
