<?php

namespace Tests\Feature\Vendor;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $vendorOwner;

    private Vendor $vendor;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->vendorOwner = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->vendor = Vendor::create([
            'user_id' => $this->vendorOwner->user_id,
            'store_name' => 'TechGear Vendor',
            'status' => 'approved',
        ]);

        $this->category = Category::create([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('vendor.reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_vendor_cannot_access_analytics_report(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('vendor.reports.index'));

        $response->assertRedirect(route('profile'));
    }

    public function test_vendor_can_view_analytics_report_page(): void
    {
        $response = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.reports.index'));

        $response->assertOk();
        $response->assertViewIs('vendor.reports.index');
        $response->assertSee('Sales & Inventory Overview');
        $response->assertSee('TechGear Vendor');
    }

    public function test_analytics_accurately_calculates_revenue_and_isolates_other_vendors(): void
    {
        $myProduct = Product::create([
            'vendor_id' => $this->vendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Mechanical Keyboard',
            'slug' => 'mechanical-keyboard',
            'description' => 'Great typing experience',
            'price' => 100.00,
            'stock' => 15,
            'status' => Product::STATUS_APPROVED,
        ]);

        $otherOwner = User::factory()->create(['role' => 'customer']);
        $otherVendor = Vendor::create([
            'user_id' => $otherOwner->user_id,
            'store_name' => 'Rival Store',
            'status' => 'approved',
        ]);
        $otherProduct = Product::create([
            'vendor_id' => $otherVendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Rival Mouse',
            'slug' => 'rival-mouse',
            'description' => 'Gaming mouse',
            'price' => 50.00,
            'stock' => 20,
            'status' => Product::STATUS_APPROVED,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        // Paid order containing both products
        $paidOrder = Order::create([
            'user_id' => $customer->user_id,
            'total_amount' => 300.00,
            'status' => OrderStatus::Paid->value,
        ]);
        Payment::create([
            'order_id' => $paidOrder->order_id,
            'provider' => 'fake',
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Succeeded,
            'amount' => 300.00,
            'currency' => 'USD',
        ]);
        OrderDetail::create([
            'order_id' => $paidOrder->order_id,
            'product_id' => $myProduct->product_id,
            'quantity' => 2,
            'unit_price' => 100.00, // $200 for my vendor
        ]);
        OrderDetail::create([
            'order_id' => $paidOrder->order_id,
            'product_id' => $otherProduct->product_id,
            'quantity' => 2,
            'unit_price' => 50.00, // $100 for rival vendor
        ]);

        // Unpaid/Pending order containing my product (should NOT be counted in revenue)
        $pendingOrder = Order::create([
            'user_id' => $customer->user_id,
            'total_amount' => 100.00,
            'status' => OrderStatus::Pending->value,
        ]);
        OrderDetail::create([
            'order_id' => $pendingOrder->order_id,
            'product_id' => $myProduct->product_id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $response = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.reports.index', ['range' => '30_days']));

        $response->assertOk();
        $response->assertSee('$200.00');
        $response->assertDontSee('Rival Mouse');
        $response->assertViewHas('metrics', function (array $metrics) {
            return $metrics['gross_revenue'] == 200.00
                && $metrics['units_sold'] == 2
                && $metrics['total_orders'] == 1
                && $metrics['total_products'] == 1;
        });
    }

    public function test_analytics_time_range_filter_excludes_older_orders(): void
    {
        $myProduct = Product::create([
            'vendor_id' => $this->vendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Vintage Shield',
            'slug' => 'vintage-shield',
            'price' => 80.00,
            'stock' => 10,
            'status' => Product::STATUS_APPROVED,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $oldOrderId = DB::table('orders')->insertGetId([
            'user_id' => $customer->user_id,
            'total_amount' => 80.00,
            'status' => OrderStatus::Paid->value,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ], 'order_id');

        DB::table('order_details')->insert([
            'order_id' => $oldOrderId,
            'product_id' => $myProduct->product_id,
            'quantity' => 1,
            'unit_price' => 80.00,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        DB::table('payments')->insert([
            'order_id' => $oldOrderId,
            'provider' => 'fake',
            'method' => PaymentMethod::Card->value,
            'amount' => 80.00,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        // 7_days should NOT include 15-day-old order
        $response7d = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.reports.index', ['range' => '7_days']))
            ->assertOk();
        $this->assertSame(0.0, (float) $response7d->viewData('metrics')['gross_revenue']);

        // 30_days SHOULD include 15-day-old order
        $response30d = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.reports.index', ['range' => '30_days']))
            ->assertOk();
        $this->assertSame(80.0, (float) $response30d->viewData('metrics')['gross_revenue']);
    }

    public function test_analytics_reports_low_stock_and_out_of_stock_alerts(): void
    {
        Product::create([
            'vendor_id' => $this->vendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Low Stock Cable',
            'slug' => 'low-stock-cable',
            'description' => 'Almost empty',
            'price' => 10.00,
            'stock' => 2,
            'status' => Product::STATUS_APPROVED,
        ]);

        Product::create([
            'vendor_id' => $this->vendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Empty Desk Mat',
            'slug' => 'empty-desk-mat',
            'description' => 'Sold out',
            'price' => 25.00,
            'stock' => 0,
            'status' => Product::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.reports.index'));

        $response->assertOk();
        $response->assertSee('Low Stock Cable');
        $response->assertSee('Empty Desk Mat');
        $response->assertViewHas('metrics', function (array $metrics) {
            return $metrics['low_stock_count'] == 1
                && $metrics['out_of_stock_count'] == 1;
        });
    }
}
