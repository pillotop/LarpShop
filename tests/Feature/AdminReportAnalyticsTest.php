<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesReportFixtures;
use Tests\TestCase;

class AdminReportAnalyticsTest extends TestCase
{
    use CreatesReportFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 22)->setTime(12, 0));
    }

    private function attempt(int $order, string $status, string $provider = 'stripe', $createdAt = null): void
    {
        DB::table('payments')->insert([
            'order_id' => $order, 'provider' => $provider, 'method' => 'card',
            'amount' => 25, 'currency' => 'USD', 'status' => $status,
            'completed_at' => $status === 'succeeded' ? now() : null,
            'created_at' => $createdAt ?? now(), 'updated_at' => now(),
        ]);
    }

    public function test_daily_trends_fill_gaps_and_keep_payment_and_creation_cohorts_separate(): void
    {
        $product = $this->makeProduct('Trend Product', 'Workshop');
        $old = $this->makeOrder($product, 1, 20, 'paid', now()->subDays(45));
        DB::table('payments')->where('order_id', $old)->update(['completed_at' => now()]);
        $this->makeOrder($product, 1, 30, 'paid', provider: 'stripe');
        $this->makeOrder($product, 1, 40, 'pending');

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();
        $trends = $response->viewData('trends');
        $points = collect($trends['points'])->keyBy('date');
        $this->assertSame('daily', $trends['granularity']);
        $this->assertCount(8, $points);
        $this->assertSame(0.0, $points['2026-11-21']['revenue']);
        $this->assertSame(0, $points['2026-11-21']['created_orders']);
        $this->assertSame(50.0, $points['2026-11-22']['revenue']);
        $this->assertSame(2, $points['2026-11-22']['paid_orders']);
        $this->assertSame(2, $points['2026-11-22']['created_orders']);
        $this->assertSame($response->viewData('metrics')['gross_revenue'], (float) $points->sum('revenue'));

        $filtered = $this->get('/admin/reports?range=7_days&sales_source=provider')->viewData('trends');
        $today = collect($filtered['points'])->last();
        $this->assertSame(30.0, $today['revenue']);
        $this->assertSame(1, $today['paid_orders']);
        $this->assertSame(2, $today['created_orders']);
    }

    public function test_equal_duration_comparison_does_not_overlap_at_the_boundary(): void
    {
        $product = $this->makeProduct('Comparison', 'Workshop');
        $this->makeOrder($product, 1, 200, 'paid', now()->subDays(14)->subSecond());
        $this->makeOrder($product, 1, 10, 'paid', now()->subDays(14));
        $this->makeOrder($product, 1, 20, 'paid', now()->subDays(7)->subSecond());
        $this->makeOrder($product, 1, 30, 'paid', now()->subDays(7));
        $this->makeOrder($product, 1, 40, 'paid');

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();
        $comparison = $response->viewData('comparison');
        $this->assertSame(70.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(30.0, $comparison['metrics']['gross_revenue']['previous']);
        $this->assertSame(40.0, $comparison['metrics']['gross_revenue']['delta']);
        $this->assertSame(133.3, $comparison['metrics']['gross_revenue']['percent']);
        $this->assertSame(2, $comparison['metrics']['total_orders']['previous']);
        $this->assertSame(2, $response->viewData('metrics')['total_orders']);
        $this->assertTrue($comparison['period']->since->equalTo(now()->subDays(14)));
        $this->assertFalse($comparison['period']->endInclusive);
        $response->assertSee('Previous: 30.00 USD')->assertDontSee('Previous: 2.00 USD');
    }

    public function test_source_filter_is_applied_to_both_sales_comparison_periods(): void
    {
        $product = $this->makeProduct('Source Comparison', 'Workshop');
        $this->makeOrder($product, 1, 50, 'paid', now()->subDays(10));
        $this->makeOrder($product, 1, 20, 'paid', now()->subDays(10), provider: 'stripe');
        $this->makeOrder($product, 1, 30, 'paid');
        $this->makeOrder($product, 1, 40, 'paid', provider: 'stripe');

        $response = $this->actingAs($this->admin())
            ->get('/admin/reports?range=7_days&sales_source=provider')->assertOk();

        $this->assertSame(40.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(20.0, $response->viewData('comparison')['metrics']['gross_revenue']['previous']);
        $this->assertSame(100.0, $response->viewData('comparison')['metrics']['gross_revenue']['percent']);
        $this->assertSame(2, $response->viewData('comparison')['metrics']['total_orders']['previous']);
    }

    public function test_zero_baseline_is_not_presented_as_infinite_growth(): void
    {
        $product = $this->makeProduct('New Sales', 'Workshop');
        $this->makeOrder($product, 1, 50, 'paid');
        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();

        $this->assertNull($response->viewData('comparison')['metrics']['gross_revenue']['percent']);
        $response->assertSee('No percentage baseline');
    }

    public function test_all_time_trends_use_months_fill_gaps_and_deduplicate_sales(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 20));
        $product = $this->makeProduct('Monthly Trend', 'Workshop');
        $january = $this->makeOrder($product, 1, 40, 'paid', now()->setDate(2026, 1, 10));
        $this->attempt($january, 'succeeded');
        $this->makeOrder($product, 1, 20, 'paid');

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=all')->assertOk();
        $trends = $response->viewData('trends');
        $this->assertSame('monthly', $trends['granularity']);
        $this->assertCount(3, $trends['points']);
        $this->assertSame(['date' => '2026-02', 'revenue' => 0.0, 'paid_orders' => 0, 'created_orders' => 0], $trends['points'][1]);
        $this->assertSame(60.0, (float) array_sum(array_column($trends['points'], 'revenue')));
        $this->assertSame(2, array_sum(array_column($trends['points'], 'paid_orders')));
        $this->assertNull($response->viewData('comparison'));
    }

    public function test_payment_report_counts_retries_and_every_current_status_without_counting_sales_twice(): void
    {
        $product = $this->makeProduct('Retry Report', 'Workshop');
        $order = $this->makeOrder($product, 1, 25, 'paid', provider: 'stripe');
        foreach (['failed', 'succeeded', 'pending'] as $status) {
            $this->attempt($order, $status);
        }
        foreach (['refunded', 'refund_required', 'delayed', 'cancelled'] as $status) {
            $this->attempt($order, $status, 'paypal');
        }
        $this->attempt($order, 'failed', 'momo_demo');
        $this->attempt($order, 'new_provider_state', 'paypal');
        $this->attempt($order, 'failed', createdAt: now()->subDays(8));
        $this->attempt($order, 'pending', createdAt: now()->addDay());

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();
        $report = $response->viewData('paymentReport');
        $providers = collect($report['providers'])->keyBy('provider');
        $this->assertSame(10, $report['total_attempts']);
        $this->assertSame(1, $report['unique_orders']);
        $this->assertSame(20.0, (float) $report['success_rate']);
        $this->assertSame(4, $providers['stripe']['attempts']);
        $this->assertSame(2, $providers['stripe']['succeeded']);
        $this->assertSame(1, $providers['stripe']['failed']);
        $this->assertSame(1, $providers['stripe']['in_progress']);
        $this->assertSame(50.0, (float) $providers['stripe']['success_rate']);
        $this->assertSame(2, $providers['paypal']['refund_related']);
        $this->assertSame(1, $providers['paypal']['cancelled']);
        $this->assertSame(1, $providers['paypal']['other']);
        $this->assertTrue($providers['momo_demo']['simulated']);
        $this->assertSame(25.0, $response->viewData('metrics')['gross_revenue']);
        $filtered = $this->get('/admin/reports?range=7_days&sales_source=simulated')->assertOk();
        $this->assertSame(10, $filtered->viewData('paymentReport')['total_attempts']);
        $this->assertSame(0.0, $filtered->viewData('metrics')['gross_revenue']);
    }

    public function test_attempts_use_creation_dates_even_when_payment_completes_later(): void
    {
        $product = $this->makeProduct('Old Attempt', 'Workshop');
        $order = $this->makeOrder($product, 1, 50, 'paid');
        DB::table('payments')->where('order_id', $order)->update(['created_at' => now()->subDays(40)]);

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();

        $this->assertSame(50.0, $response->viewData('metrics')['gross_revenue']);
        $this->assertSame(0, $response->viewData('paymentReport')['total_attempts']);
        $response->assertSee('No payment attempts were created in this period.');
    }

    public function test_empty_report_has_finite_zeroes_and_provider_labels_are_escaped(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/reports')->assertOk();
        $this->assertSame(0.0, $response->viewData('comparison')['metrics']['gross_revenue']['percent']);
        $this->assertSame(0, $response->viewData('paymentReport')['success_rate']);

        $product = $this->makeProduct('Escaped Label', 'Workshop');
        $this->makeOrder($product, 1, 25, 'paid', provider: 'stripe');
        config(['payments.providers.stripe.label' => '<script>alert(1)</script>']);
        $this->get('/admin/reports')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_populated_analytics_are_localized_in_vietnamese(): void
    {
        $product = $this->makeProduct('Localized Analytics', 'Workshop');
        $this->makeOrder($product, 1, 25, 'paid', provider: 'momo_demo');

        $response = $this->actingAs($this->admin())->withSession(['locale' => 'vi'])
            ->get('/admin/reports?range=7_days')->assertOk();

        $response->assertSee(__('management.report_copy.payment_activity', [], 'vi'))
            ->assertSee(__('management.report_copy.simulation', [], 'vi'))
            ->assertSee(__('management.report_copy.view_trend_data', [], 'vi'));
        $this->assertStringNotContainsString('management.', strip_tags($response->getContent()));
    }
}
