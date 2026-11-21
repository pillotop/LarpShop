<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesReportFixtures;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use CreatesReportFixtures, RefreshDatabase;

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
        $this->makeOrder($product, 2, 50, 'paid');
        $this->makeOrder($product, 1, 999, 'cancelled');
        $this->makeOrder($product, 1, 888, 'paid', paymentStatus: 'refunded');

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();

        $this->assertSame(100.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(3, $response->viewData('metrics')['total_orders']);
        $this->assertSame(100.0, (float) $response->viewData('metrics')['average_order_value']);
    }

    public function test_revenue_requires_payment_evidence_and_does_not_duplicate_order_lines(): void
    {
        $product = $this->makeProduct('Verified Sword', 'Verified Store');
        $this->makeOrder($product, 1, 999, 'paid', paymentStatus: null);
        $id = $this->makeOrder($product, 2, 25, 'paid');
        DB::table('payments')->insert([
            'order_id' => $id, 'provider' => 'fake', 'method' => 'card',
            'amount' => 50, 'currency' => 'USD', 'status' => 'succeeded',
            'completed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();
        $this->assertSame(50.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(50.0, (float) $response->viewData('topProducts')->first()->revenue);
        $this->assertSame(2, (int) $response->viewData('topProducts')->first()->units_sold);
        $response->assertDontSee('Delivered / Disputed');
    }

    public function test_time_range_filter_excludes_old_orders(): void
    {
        $product = $this->makeProduct('Iron Sword', 'Forge Store');
        $this->makeOrder($product, 1, 40, 'paid');
        $this->makeOrder($product, 1, 500, 'paid', now()->subDays(60));

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
        $this->makeOrder($cheap, 1, 10, 'paid');
        $this->makeOrder($big, 3, 100, 'paid');

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
        $this->makeOrder($product, 1, 10, 'paid');
        $this->makeOrder($product, 1, 10, 'expired');

        $dist = $this->actingAs($this->admin())->get('/admin/reports?range=all')->viewData('statusDistribution');

        $this->assertSame(1, $dist['paid']['count']);
        $this->assertSame(50.0, (float) $dist['expired']['percent']);
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

    public function test_sales_follow_payment_completion_while_statuses_follow_order_creation(): void
    {
        $product = $this->makeProduct('Late Payment', 'Workshop');
        $oldOrder = $this->makeOrder($product, 2, 25, 'paid', now()->subDays(60));
        DB::table('payments')->where('order_id', $oldOrder)->update(['completed_at' => now()]);
        $this->makeOrder($product, 1, 80, 'paid', now()->subDays(20));

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();
        $metrics = $response->viewData('metrics');

        $this->assertSame(50.0, $metrics['gross_revenue']);
        $this->assertSame(1, $metrics['paid_orders']);
        $this->assertSame(0, $metrics['total_orders']);
        $this->assertSame(50.0, (float) $response->viewData('topProducts')->first()->revenue);
        $this->assertSame(50.0, (float) $response->viewData('topVendors')->first()->revenue);
        $response->assertSee('50.00 USD')->assertSee('Orders paid in this period');
    }

    public function test_duplicate_success_in_another_period_does_not_repeat_the_sale(): void
    {
        $product = $this->makeProduct('Duplicate Success', 'Workshop');
        $order = $this->makeOrder($product, 1, 50, 'paid', now()->subDays(60));
        DB::table('payments')->insert([
            'order_id' => $order, 'provider' => 'paypal', 'method' => 'ewallet',
            'amount' => 50, 'currency' => 'USD', 'status' => 'succeeded',
            'completed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->admin());
        $recent = $this->get('/admin/reports?range=7_days')->assertOk();
        $this->assertSame(0.0, $recent->viewData('metrics')['gross_revenue']);
        $this->assertTrue($recent->viewData('topProducts')->isEmpty());

        $provider = $this->get('/admin/reports?range=all&sales_source=provider')->assertOk();
        $this->assertSame(0, $provider->viewData('metrics')['paid_orders']);
        $all = $this->get('/admin/reports?range=all')->assertOk();
        $this->assertSame(50.0, $all->viewData('metrics')['gross_revenue']);
    }

    public function test_sales_sources_separate_simulations_and_provider_payments(): void
    {
        $product = $this->makeProduct('Payment Sources', 'Workshop');
        $this->makeOrder($product, 1, 10, 'paid');
        $this->makeOrder($product, 1, 20, 'paid', provider: 'momo_demo');
        $this->makeOrder($product, 1, 30, 'paid', provider: 'stripe');
        $this->makeOrder($product, 1, 40, 'paid', provider: 'paypal');

        $this->actingAs($this->admin());
        foreach (['all' => [100.0, 4], 'simulated' => [30.0, 2], 'provider' => [70.0, 2]] as $source => [$revenue, $count]) {
            $response = $this->get('/admin/reports?range=all&sales_source='.$source)->assertOk();
            $metrics = $response->viewData('metrics');
            $this->assertSame($revenue, $metrics['gross_revenue']);
            $this->assertSame($count, $metrics['paid_orders']);
            $this->assertSame(4, $metrics['total_orders']);
            $this->assertSame($revenue / $count, $metrics['average_order_value']);
            $this->assertSame($revenue, (float) $response->viewData('topProducts')->first()->revenue);
            $this->assertSame($revenue, (float) $response->viewData('topVendors')->first()->revenue);
            $response->assertSee('value="'.$source.'" selected', false);
        }
    }

    public function test_converted_payments_use_order_prices_without_summing_settlement_currencies(): void
    {
        $product = $this->makeProduct('Converted Payment', 'Workshop', 999);
        $order = $this->makeOrder($product, 2, 25, 'paid', provider: 'momo');
        DB::table('payments')->where('order_id', $order)->update([
            'amount' => 1250000, 'currency' => 'VND',
            'source_amount' => 50, 'source_currency' => 'USD', 'exchange_rate' => 25000,
        ]);
        $this->makeOrder($product, 1, 30, 'paid', provider: 'paypal');

        $response = $this->actingAs($this->admin())
            ->withSession(['currency' => 'VND'])
            ->get('/admin/reports?range=all')->assertOk();

        $this->assertSame(80.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(80.0, (float) $response->viewData('topProducts')->first()->revenue);
        $this->assertSame('USD', $response->viewData('reportCurrency'));
        $response->assertSee('80.00 USD');
    }

    public function test_undated_successes_are_reported_and_excluded_without_guessing_a_date(): void
    {
        $product = $this->makeProduct('Legacy Payment', 'Workshop');
        $order = $this->makeOrder($product, 1, 50, 'paid');
        DB::table('payments')->where('order_id', $order)->update(['completed_at' => null]);

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();

        $this->assertSame(1, $response->viewData('undatedSuccesses'));
        $this->assertSame(0.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(1, $response->viewData('metrics')['total_orders']);
        $this->assertTrue($response->viewData('topProducts')->isEmpty());
        $response->assertSee('Successful payment records without a completion date: 1');
    }

    public function test_future_orders_and_payments_are_not_included(): void
    {
        $product = $this->makeProduct('Future Payment', 'Workshop');
        $order = $this->makeOrder($product, 1, 50, 'paid');
        DB::table('payments')->where('order_id', $order)->update(['completed_at' => now()->addDay()]);
        $this->makeOrder($product, 1, 200, 'paid', now()->addDay());

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();

        $this->assertSame(0.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(1, $response->viewData('metrics')['total_orders']);
    }

    public function test_current_snapshot_is_independent_of_report_filters(): void
    {
        $product = $this->makeProduct('Current Product', 'Workshop');
        DB::table('products')->where('product_id', $product)->update([
            'status' => 'approved', 'created_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/reports?range=7_days&sales_source=provider')->assertOk();

        $this->assertSame(1, $response->viewData('metrics')['active_vendors']);
        $this->assertSame(1, $response->viewData('metrics')['approved_products']);
        $response->assertSee('Current platform snapshot')
            ->assertSee('Period and sales source filters do not apply.');
    }

    public function test_month_boundaries_and_report_cutoff_use_completion_time(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10)->setTime(12, 0));
        $product = $this->makeProduct('Month Boundary', 'Workshop');
        $before = $this->makeOrder($product, 1, 100, 'paid');
        $atStart = $this->makeOrder($product, 1, 20, 'paid');
        $this->makeOrder($product, 1, 30, 'paid');
        DB::table('payments')->where('order_id', $before)->update([
            'completed_at' => now()->startOfMonth()->subSecond(),
        ]);
        DB::table('payments')->where('order_id', $atStart)->update([
            'completed_at' => now()->startOfMonth(),
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=this_month')->assertOk();

        $this->assertSame(50.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(2, $response->viewData('metrics')['paid_orders']);
        $this->assertSame(3, $response->viewData('metrics')['total_orders']);
        $this->assertTrue($response->viewData('reportPeriod')->since->equalTo(now()->startOfMonth()));
        $this->assertTrue($response->viewData('reportPeriod')->until->equalTo(now()));
    }

    public function test_unsupported_sources_and_array_filters_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/reports?sales_source=live')->assertSessionHasErrors('sales_source');
        $this->get('/admin/reports?sales_source[]=provider')->assertSessionHasErrors('sales_source');
        $this->get('/admin/reports?range[]=all')->assertSessionHasErrors('range');
    }
}
