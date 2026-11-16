<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class PurchaseHistoryTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    /**
     * @param  list<array{0: Product, 1?: int, 2?: FulfillmentStatus}>  $lines
     */
    private function order(
        User $user,
        OrderStatus $status,
        array $lines,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $expiresAt = null,
    ): Order {
        $total = 0;
        foreach ($lines as $line) {
            $total += (float) $line[0]->price * ($line[1] ?? 1);
        }

        $order = Order::create([
            'user_id' => $user->getKey(),
            'total_amount' => $total,
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);

        foreach ($lines as $line) {
            $order->details()->create([
                'product_id' => $line[0]->getKey(),
                'quantity' => $line[1] ?? 1,
                'unit_price' => $line[0]->price,
                'fulfillment_status' => ($line[2] ?? FulfillmentStatus::Unfulfilled)->value,
            ]);
        }

        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
        }

        return $order;
    }

    public function test_profile_shows_purchase_history_tab_after_stores(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee('id="tab-purchases"', false)
            ->assertSee('id="pane-purchases"', false)
            ->assertSee('Purchase history')
            ->assertSee('No orders were found')
            ->getContent();

        $this->assertLessThan(strpos($html, 'id="tab-purchases"'), strpos($html, 'id="tab-vendor-info"'));
        $this->assertLessThan(strpos($html, 'id="tab-security"'), strpos($html, 'id="tab-purchases"'));
    }

    public function test_guests_cannot_view_purchase_history(): void
    {
        $this->get(route('profile'))->assertRedirect(route('login'));
    }

    public function test_only_the_signed_in_users_orders_are_listed(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->product('Mine Sword');
        $theirs = $this->product('Their Shield');

        $this->order($user, OrderStatus::Paid, [[$mine, 2]]);
        $this->order($other, OrderStatus::Paid, [[$theirs]]);

        $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee('Mine Sword')
            ->assertSee('Sold by Mine Sword Store')
            ->assertSee('2 × $12.50')
            ->assertSee('$25.00')
            ->assertDontSee('Their Shield');
    }

    public function test_status_filters_select_the_matching_orders(): void
    {
        $user = User::factory()->create();

        $this->order($user, OrderStatus::Pending, [[$this->product('Unpaid Item')]]);
        $this->order($user, OrderStatus::Paid, [[$this->product('Processing Item'), 1, FulfillmentStatus::Processing]]);
        $this->order($user, OrderStatus::Paid, [[$this->product('Shipped Item'), 1, FulfillmentStatus::Shipped]]);
        $this->order($user, OrderStatus::Paid, [[$this->product('Delivered Item'), 1, FulfillmentStatus::Delivered]]);
        $this->order($user, OrderStatus::Cancelled, [[$this->product('Cancelled Item')]]);
        $this->order($user, OrderStatus::Expired, [[$this->product('Expired Item')]]);

        $names = ['Unpaid Item', 'Processing Item', 'Shipped Item', 'Delivered Item', 'Cancelled Item', 'Expired Item'];
        $expected = [
            'unpaid' => ['Unpaid Item'],
            'processing' => ['Processing Item'],
            'shipped' => ['Shipped Item'],
            'delivered' => ['Delivered Item'],
            'cancelled' => ['Cancelled Item', 'Expired Item'],
        ];

        foreach ($expected as $filter => $visible) {
            $response = $this->actingAs($user)->get(route('profile', ['orders_filter' => $filter]))->assertOk();

            foreach ($names as $name) {
                in_array($name, $visible, true)
                    ? $response->assertSee($name)
                    : $response->assertDontSee($name);
            }
        }
    }

    public function test_ready_for_feedback_and_review_button_only_for_unreviewed_paid_products(): void
    {
        $user = User::factory()->create();
        $reviewed = $this->product('Reviewed Item');
        $fresh = $this->product('Fresh Item');
        $pending = $this->product('Pending Item');

        ProductReview::forceCreate([
            'product_id' => $reviewed->getKey(),
            'user_id' => $user->getKey(),
            'rating' => 5,
            'comment' => 'Already reviewed.',
        ]);
        $this->order($user, OrderStatus::Paid, [[$reviewed]]);
        $this->order($user, OrderStatus::Paid, [[$fresh]]);
        $this->order($user, OrderStatus::Pending, [[$pending]]);

        $this->actingAs($user)->get(route('profile', ['orders_filter' => 'feedback']))
            ->assertOk()
            ->assertSee('Fresh Item')
            ->assertSee('Write a review')
            ->assertSee('#product-reviews', false)
            ->assertDontSee('Reviewed Item')
            ->assertDontSee('Pending Item');

        $this->get(route('profile'))
            ->assertOk()
            ->assertSee('Reviewed Item')
            ->assertSee('Pending Item');
    }

    public function test_search_matches_product_name_or_order_number(): void
    {
        $user = User::factory()->create();
        $sword = $this->order($user, OrderStatus::Paid, [[$this->product('Moonlit Sword')]]);
        $this->order($user, OrderStatus::Paid, [[$this->product('Iron Shield')]]);

        $this->actingAs($user)->get(route('profile', ['orders_q' => 'moonlit']))
            ->assertOk()
            ->assertSee('Moonlit Sword')
            ->assertDontSee('Iron Shield');

        $this->get(route('profile', ['orders_q' => '#'.$sword->getKey()]))
            ->assertOk()
            ->assertSee('Moonlit Sword')
            ->assertDontSee('Iron Shield');

        $this->get(route('profile', ['orders_q' => 'does-not-exist']))
            ->assertOk()
            ->assertSee('No orders match your search or filters.')
            ->assertDontSee('Moonlit Sword');
    }

    public function test_period_filter_hides_older_orders(): void
    {
        $user = User::factory()->create();
        $this->order($user, OrderStatus::Paid, [[$this->product('Recent Item')]], now()->subDays(5));
        $this->order($user, OrderStatus::Paid, [[$this->product('Old Item')]], now()->subDays(200));

        $this->actingAs($user)->get(route('profile', ['orders_period' => '30d']))
            ->assertSee('Recent Item')
            ->assertDontSee('Old Item');

        $this->get(route('profile', ['orders_period' => '1y']))
            ->assertSee('Recent Item')
            ->assertSee('Old Item');
    }

    public function test_malformed_filter_values_fall_back_to_defaults(): void
    {
        $user = User::factory()->create();
        $this->order($user, OrderStatus::Paid, [[$this->product('Plain Item')]]);

        $this->actingAs($user)->get('/profile?orders_filter=drop-table&orders_period[]=x&orders_q[]=y&orders_page=abc')
            ->assertOk()
            ->assertSee('Plain Item');

        $this->get(route('profile', ['orders_q' => str_repeat('a', 500)]))->assertOk();
    }

    public function test_pending_orders_offer_payment_and_cancel_unless_expired(): void
    {
        $user = User::factory()->create();
        $open = $this->order($user, OrderStatus::Pending, [[$this->product('Open Item')]], null, now()->addMinutes(20));
        $stale = $this->order($user, OrderStatus::Pending, [[$this->product('Stale Item')]], null, now()->subMinutes(5));
        $paid = $this->order($user, OrderStatus::Paid, [[$this->product('Paid Item')]]);

        $html = $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee(route('checkout.show', $open), false)
            ->assertSee(route('orders.cancel', $open), false)
            ->assertSee(route('orders.cancel', $stale), false)
            ->assertDontSee(route('checkout.show', $stale), false)
            ->assertDontSee(route('orders.cancel', $paid), false)
            ->assertSee(route('orders.show', $paid), false)
            ->getContent();

        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_unavailable_products_are_not_linked_or_offered_again(): void
    {
        $user = User::factory()->create();
        $product = $this->product('Retired Item');
        $product->forceFill(['status' => Product::STATUS_REJECTED])->save();
        $this->order($user, OrderStatus::Paid, [[$product]]);

        $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee('Retired Item')
            ->assertSee('This product is no longer available.')
            ->assertDontSee('Buy again')
            ->assertDontSee('Write a review')
            ->assertDontSee(route('products.show', ['product' => $product->getKey(), 'slug' => $product->slug]), false);
    }

    public function test_available_in_stock_products_offer_buy_again(): void
    {
        $user = User::factory()->create();
        $product = $this->product('Stocked Item', stock: 4);
        $soldOut = $this->product('Sold Out Item', stock: 0);
        $this->order($user, OrderStatus::Paid, [[$product], [$soldOut]]);

        $html = $this->actingAs($user)->get(route('profile'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'Buy again'));
        $this->assertStringContainsString(
            route('products.show', ['product' => $product->getKey(), 'slug' => $product->slug]),
            $html,
        );
    }

    public function test_pagination_keeps_filters_and_returns_to_the_purchases_tab(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 7; $i++) {
            $this->order($user, OrderStatus::Paid, [[$this->product('Bulk Item '.$i)]], now()->subMinutes(10 - $i));
        }

        $this->actingAs($user)->get(route('profile', ['orders_filter' => 'processing', 'orders_period' => '90d']))
            ->assertOk()
            ->assertSee('Bulk Item 7')
            ->assertSee('Bulk Item 3')
            ->assertDontSee('Bulk Item 2')
            ->assertSee('orders_filter=processing', false)
            ->assertSee('orders_page=2', false)
            ->assertSee('#pane-purchases', false);

        $this->get(route('profile', ['orders_filter' => 'processing', 'orders_page' => 2]))
            ->assertOk()
            ->assertSee('Bulk Item 2')
            ->assertSee('Bulk Item 1')
            ->assertDontSee('Bulk Item 7');
    }

    public function test_product_names_are_escaped_and_labels_are_translated(): void
    {
        $user = User::factory()->create();
        $name = '<script>alert("history")</script>';
        $this->order($user, OrderStatus::Paid, [[$this->product($name)]]);

        $this->actingAs($user)->withSession(['locale' => 'vi'])->get(route('profile'))
            ->assertOk()
            ->assertSee('Lịch sử mua hàng')
            ->assertSee('Xem chi tiết đơn hàng')
            ->assertSee($name)
            ->assertDontSee($name, false);
    }
}
