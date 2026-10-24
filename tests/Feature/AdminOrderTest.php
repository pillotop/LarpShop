<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Creates an order with two items from two different vendors.
     */
    private function makeOrder(User $buyer, string $status = 'pending', float $total = 70.00): Order
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Gear', 'slug' => 'gear-'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');

        $products = [];
        foreach (['Alpha Armory' => 20.00, 'Beta Blades' => 25.00] as $store => $price) {
            $owner = User::factory()->create();
            $vendorId = DB::table('vendors')->insertGetId([
                'user_id' => $owner->user_id, 'store_name' => $store.uniqid(), 'status' => 'approved',
                'created_at' => now(), 'updated_at' => now(),
            ], 'vendor_id');
            $products[] = [DB::table('products')->insertGetId([
                'category_id' => $categoryId, 'vendor_id' => $vendorId, 'name' => $store.' Item',
                'slug' => 'item-'.uniqid(), 'price' => $price, 'stock' => 5,
                'created_at' => now(), 'updated_at' => now(),
            ], 'product_id'), $price];
        }

        $order = Order::create(['user_id' => $buyer->user_id, 'total_amount' => $total, 'status' => $status]);
        // 2 x 20.00 + 1 x 25.00 = 65.00 (differs from default total 70.00 to exercise mismatch)
        $order->details()->create(['product_id' => $products[0][0], 'quantity' => 2, 'unit_price' => $products[0][1]]);
        $order->details()->create(['product_id' => $products[1][0], 'quantity' => 1, 'unit_price' => $products[1][1]]);

        return $order;
    }

    public function test_non_admins_cannot_access_order_investigation(): void
    {
        $this->get('/admin/orders')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($customer)->get('/admin/orders')->assertForbidden();
        $this->actingAs($customer)->get("/admin/orders/{$order->order_id}")->assertForbidden();
        $this->actingAs($customer)->patch("/admin/orders/{$order->order_id}/status", ['status' => 'refunded'])->assertForbidden();
        $this->assertSame('pending', $order->refresh()->status);
    }

    public function test_admin_can_list_filter_and_search_orders(): void
    {
        $alice = User::factory()->create(['username' => 'alice_buyer']);
        $bob = User::factory()->create(['username' => 'bob_buyer']);
        $aliceOrder = $this->makeOrder($alice, 'disputed');
        $bobOrder = $this->makeOrder($bob, 'delivered');
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/orders')
            ->assertOk()->assertSee('alice_buyer')->assertSee('bob_buyer');

        $this->actingAs($admin)->get('/admin/orders?status=disputed')
            ->assertOk()->assertSee('alice_buyer')->assertDontSee('bob_buyer');

        $this->actingAs($admin)->get('/admin/orders?q=bob_buyer')
            ->assertOk()->assertSee('bob_buyer')->assertDontSee('alice_buyer');

        $this->actingAs($admin)->get('/admin/orders?q=%23'.$aliceOrder->order_id)
            ->assertOk()->assertSee('alice_buyer')->assertDontSee('bob_buyer');
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $this->actingAs($this->admin())->get('/admin/orders?status=hacked')->assertSessionHasErrors('status');
    }

    public function test_admin_can_investigate_multi_vendor_order_and_see_total_mismatch(): void
    {
        $buyer = User::factory()->create(['username' => 'carol_buyer']);
        $order = $this->makeOrder($buyer, 'pending', 70.00);

        $this->actingAs($this->admin())->get("/admin/orders/{$order->order_id}")
            ->assertOk()
            ->assertSee('carol_buyer')
            ->assertSee('Alpha Armory')
            ->assertSee('Beta Blades')
            ->assertSee('Total mismatch detected');
    }

    public function test_matching_total_is_reported_as_consistent(): void
    {
        $order = $this->makeOrder(User::factory()->create(), 'pending', 65.00);

        $this->actingAs($this->admin())->get("/admin/orders/{$order->order_id}")
            ->assertOk()->assertSee('Totals match.')->assertDontSee('Total mismatch detected');
    }

    public function test_unknown_order_returns_404(): void
    {
        $this->actingAs($this->admin())->get('/admin/orders/99999')->assertNotFound();
    }

    public function test_admin_can_update_order_status(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs($this->admin())->patch("/admin/orders/{$order->order_id}/status", ['status' => 'disputed'])
            ->assertSessionHas('success');

        $this->assertSame('disputed', $order->refresh()->status);
    }

    public function test_order_status_must_be_in_allowlist(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        foreach (['hacked', '', 'PENDING '] as $bad) {
            $this->actingAs($this->admin())->patch("/admin/orders/{$order->order_id}/status", ['status' => $bad])
                ->assertSessionHasErrors('status');
        }

        $this->assertSame('pending', $order->refresh()->status);
    }
}
