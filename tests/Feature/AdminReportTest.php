<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeProduct(string $name, string $store, float $price = 10): int
    {
        $owner = User::factory()->create();
        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $owner->user_id, 'store_name' => $store, 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ], 'vendor_id');
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Cat '.uniqid(), 'slug' => 'cat-'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'vendor_id' => $vendorId, 'name' => $name,
            'slug' => 'p-'.uniqid(), 'price' => $price, 'stock' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ], 'product_id');
    }

    private function makeOrder(int $productId, int $qty, float $unitPrice, string $status, $createdAt = null): void
    {
        $buyer = User::factory()->create();
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $buyer->user_id, 'total_amount' => $qty * $unitPrice, 'status' => $status,
            'created_at' => $createdAt ?? now(), 'updated_at' => now(),
        ], 'order_id');
        DB::table('order_details')->insert([
            'order_id' => $orderId, 'product_id' => $productId, 'quantity' => $qty,
            'unit_price' => $unitPrice, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_non_admins_cannot_access_reports(): void
    {
        $this->get('/admin/reports')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/reports')->assertForbidden();
    }

    public function test_admin_can_view_empty_report(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Platform Overview')
            ->assertSee('No orders in this period.');
    }

    public function test_revenue_ignores_cancelled_and_refunded_orders(): void
    {
        $product = $this->makeProduct('Iron Sword', 'Forge Store');
        $this->makeOrder($product, 2, 50, 'delivered');
        $this->makeOrder($product, 1, 999, 'cancelled');
        $this->makeOrder($product, 1, 888, 'refunded');

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();

        $this->assertSame(100.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(3, $response->viewData('metrics')['total_orders']);
        $this->assertSame(100.0, (float) $response->viewData('metrics')['average_order_value']);
    }

    public function test_time_range_filter_excludes_old_orders(): void
    {
        $product = $this->makeProduct('Iron Sword', 'Forge Store');
        $this->makeOrder($product, 1, 40, 'delivered');
        $this->makeOrder($product, 1, 500, 'delivered', now()->subDays(60));

        $recent = $this->actingAs($this->admin())->get('/admin/reports?range=7_days');
        $this->assertSame(40.0, $recent->viewData('metrics')['gross_revenue']);

        $all = $this->actingAs($this->admin())->get('/admin/reports?range=all');
        $this->assertSame(540.0, $all->viewData('metrics')['gross_revenue']);
    }

    public function test_invalid_range_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/reports?range=bogus')
            ->assertSessionHasErrors('range');
    }

    public function test_top_products_and_vendors_are_ranked_by_revenue(): void
    {
        $cheap = $this->makeProduct('Cheap Cape', 'Small Shop');
        $big = $this->makeProduct('Dragon Armor', 'Big Shop');
        $this->makeOrder($cheap, 1, 10, 'delivered');
        $this->makeOrder($big, 3, 100, 'shipped');

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();

        $products = $response->viewData('topProducts');
        $this->assertSame('Dragon Armor', $products->first()->name);
        $this->assertEquals(300, $products->first()->revenue);

        $vendors = $response->viewData('topVendors');
        $this->assertSame('Big Shop', $vendors->first()->store_name);
        $response->assertSee('Dragon Armor')->assertSee('Big Shop');
    }

    public function test_status_distribution_percentages(): void
    {
        $product = $this->makeProduct('Iron Sword', 'Forge Store');
        $this->makeOrder($product, 1, 10, 'delivered');
        $this->makeOrder($product, 1, 10, 'disputed');

        $dist = $this->actingAs($this->admin())->get('/admin/reports?range=all')->viewData('statusDistribution');

        $this->assertSame(1, $dist['delivered']['count']);
        $this->assertSame(50.0, (float) $dist['disputed']['percent']);
    }

    public function test_category_distribution_counts_only_approved_products(): void
    {
        $owner = User::factory()->create();
        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $owner->user_id, 'store_name' => 'Armory', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ], 'vendor_id');
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Shields', 'slug' => 'shields', 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');

        DB::table('products')->insert([
            [
                'category_id' => $categoryId, 'vendor_id' => $vendorId, 'name' => 'Oak Shield',
                'slug' => 'oak-shield', 'price' => 25, 'stock' => 5, 'status' => 'approved',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'category_id' => $categoryId, 'vendor_id' => $vendorId, 'name' => 'Draft Shield',
                'slug' => 'draft-shield', 'price' => 25, 'stock' => 5, 'status' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $categories = $this->actingAs($this->admin())
            ->get('/admin/reports')
            ->assertOk()
            ->viewData('categoryDistribution');

        $shields = $categories->firstWhere('category_id', $categoryId);
        $this->assertNotNull($shields);
        $this->assertSame(1, $shields->products_count);
    }
}
