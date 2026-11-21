<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesReportFixtures;
use Tests\TestCase;

class AdminReportOperationsTest extends TestCase
{
    use CreatesReportFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 22)->setTime(12, 0));
    }

    private function catalogProduct(string $name, int $stock): int
    {
        $id = $this->makeProduct($name, $name.' Store');
        DB::table('products')->where('product_id', $id)->update([
            'status' => 'approved', 'is_active' => true, 'stock' => $stock,
            'created_at' => now()->subDays(60),
        ]);

        return $id;
    }

    private function application(string $status, $submittedAt = null, $reviewedAt = null): VendorApplication
    {
        return VendorApplication::factory()->create([
            'store_name' => 'Application '.uniqid(), 'status' => $status,
            'submitted_at' => $submittedAt, 'reviewed_at' => $reviewedAt,
        ]);
    }

    private function review(int $product, int $rating, $createdAt = null, ?string $comment = null): int
    {
        return DB::table('product_reviews')->insertGetId([
            'product_id' => $product, 'user_id' => User::factory()->create()->user_id,
            'rating' => $rating, 'comment' => $comment,
            'created_at' => $createdAt ?? now(), 'updated_at' => now(),
        ], 'review_id');
    }

    public function test_empty_operations_have_zero_counts_and_no_invented_averages(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/reports')->assertOk();

        $this->assertSame(0, $response->viewData('inventoryReport')['total_products']);
        $this->assertSame(0, $response->viewData('fulfillmentReport')['lines']);
        $this->assertSame(0, $response->viewData('fulfillmentReport')['awaiting_dispatch']);
        $this->assertNull($response->viewData('applicationReport')['average_review_hours']);
        $this->assertNull($response->viewData('reviewReport')['average']);
        $this->assertCount(5, $response->viewData('reviewReport')['ratings']);
        $response->assertSee('Platform operations')->assertSee('No submitted applications are awaiting review.');
    }

    public function test_inventory_reuses_catalog_visibility_and_disjoint_stock_bands(): void
    {
        foreach ([0, 1, 5, 6] as $stock) {
            $this->catalogProduct('Stock '.$stock, $stock);
        }
        $hidden = $this->catalogProduct('Hidden Product', 0);
        DB::table('products')->where('product_id', $hidden)->update(['is_active' => false]);
        $suspended = $this->catalogProduct('Suspended Store Product', 0);
        $vendor = DB::table('products')->where('product_id', $suspended)->value('vendor_id');
        DB::table('vendors')->where('vendor_id', $vendor)->update(['status' => 'suspended']);
        $pending = $this->makeProduct('Pending Moderation', 'Workshop');
        DB::table('products')->where('product_id', $pending)->update(['status' => 'pending']);
        $rejected = $this->catalogProduct('Rejected Product', 0);
        DB::table('products')->where('product_id', $rejected)->update(['status' => 'rejected']);

        $response = $this->actingAs($this->admin())
            ->get('/admin/reports?range=7_days&sales_source=provider')->assertOk();
        $report = $response->viewData('inventoryReport');

        $this->assertSame(4, $report['total_products']);
        $this->assertSame(12, $report['available_units']);
        $this->assertSame(['out_of_stock' => 1, 'low_stock' => 2, 'healthy_stock' => 1], $report['bands']);
        $this->assertSame(1, $report['pending_products']);
        $this->assertSame([0, 1, 5], $report['attention_products']->pluck('stock')->all());
        $this->assertNotContains($pending, $report['attention_products']->modelKeys());
        $response->assertSee(route('admin.products.moderation.index', ['status' => 'pending']), false);
    }

    public function test_inventory_attention_is_bounded_and_ordered_by_stock_then_id(): void
    {
        $ids = [];
        foreach ([5, 4, 3, 2, 1, 0, 0] as $index => $stock) {
            $ids[] = $this->catalogProduct('Attention '.$index, $stock);
        }
        $report = $this->actingAs($this->admin())->get('/admin/reports')->assertOk()->viewData('inventoryReport');

        $this->assertCount(5, $report['attention_products']);
        $this->assertSame([$ids[5], $ids[6], $ids[4], $ids[3], $ids[2]], $report['attention_products']->modelKeys());
    }

    public function test_fulfillment_counts_lines_and_units_across_vendors_with_a_separate_all_time_queue(): void
    {
        $first = $this->makeProduct('First Vendor Item', 'First Store');
        $second = $this->makeProduct('Second Vendor Item', 'Second Store');
        $order = $this->makeOrder($first, 2, 10, 'paid');
        DB::table('order_details')->insert([
            'order_id' => $order, 'product_id' => $second, 'quantity' => 3, 'unit_price' => 10,
            'fulfillment_status' => 'processing', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $delivered = $this->makeOrder($second, 4, 10, 'paid');
        DB::table('order_details')->where('order_id', $delivered)->update(['fulfillment_status' => 'delivered']);
        $old = $this->makeOrder($first, 5, 10, 'paid', now()->subDays(60));
        $this->makeOrder($first, 9, 10, 'cancelled');
        $this->makeOrder($first, 9, 10, 'paid', now()->addDay());
        $payment = (array) DB::table('payments')->where('order_id', $order)->first();
        unset($payment['payment_id']);
        DB::table('payments')->insert($payment);

        $response = $this->actingAs($this->admin())
            ->get('/admin/reports?range=7_days&sales_source=provider')->assertOk();
        $report = $response->viewData('fulfillmentReport');

        $this->assertSame(2, $report['orders']);
        $this->assertSame(3, $report['lines']);
        $this->assertSame(9, $report['units']);
        $this->assertSame(['lines' => 1, 'units' => 2], $report['statuses']['unfulfilled']);
        $this->assertSame(['lines' => 1, 'units' => 3], $report['statuses']['processing']);
        $this->assertSame(['lines' => 1, 'units' => 4], $report['statuses']['delivered']);
        $this->assertSame(3, $report['awaiting_dispatch']);
        $this->assertSame($old, $report['oldest_lines']->first()->order_id);
        $this->assertSame(0.0, $response->viewData('metrics')['gross_revenue']);
        $response->assertSee(route('admin.orders.show', $order), false);
    }

    public function test_fulfillment_matches_paid_status_workflow_even_without_dated_payment_evidence(): void
    {
        $product = $this->makeProduct('Legacy Paid Item', 'Workshop');
        $this->makeOrder($product, 2, 25, 'paid', paymentStatus: null);
        $response = $this->actingAs($this->admin())->get('/admin/reports')->assertOk();

        $this->assertSame(1, $response->viewData('fulfillmentReport')['orders']);
        $this->assertSame(1, $response->viewData('fulfillmentReport')['awaiting_dispatch']);
        $this->assertSame(0.0, $response->viewData('metrics')['gross_revenue']);
    }

    public function test_applications_use_latest_submission_cohort_and_only_valid_review_durations(): void
    {
        $old = $this->application('submitted', now()->subDays(60));
        $this->application('submitted', now()->subHours(6), now()->subDays(10));
        $this->application('approved', now()->subDays(2), now()->subDay());
        $this->application('rejected', now()->subDays(4), now()->subDays(2));
        $this->application('rejected', now()->subDay(), now()->subDays(2));
        $this->application('approved', now()->subDay(), now()->addDay());
        $this->application('rejected');
        $this->application('draft');
        $this->application('submitted', now()->addDay());
        $product = $this->makeProduct('Legacy Vendor Product', 'Legacy Store');
        DB::table('vendors')->where('vendor_id', DB::table('products')->where('product_id', $product)->value('vendor_id'))
            ->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin())->get('/admin/reports?range=7_days')->assertOk();
        $report = $response->viewData('applicationReport');

        $this->assertSame(5, $report['submitted']);
        $this->assertSame(['submitted' => 1, 'approved' => 2, 'rejected' => 2], $report['statuses']);
        $this->assertSame(36.0, $report['average_review_hours']);
        $this->assertSame(2, $report['review_samples']);
        $this->assertSame(2, $report['pending_now']);
        $this->assertSame($old->getKey(), $report['oldest_pending']->first()->getKey());
        $this->assertSame(1, $report['legacy_pending']);
    }

    public function test_application_review_link_targets_the_existing_vendor_investigation_page(): void
    {
        $product = $this->makeProduct('Vendor Application Product', 'Linked Store');
        $vendor = DB::table('products')->where('product_id', $product)->value('vendor_id');
        $application = $this->application('submitted', now()->subDay());
        $application->vendor_id = $vendor;
        $application->save();

        $this->actingAs($this->admin())->get('/admin/reports')->assertOk()
            ->assertSee(route('admin.vendors.show', $vendor), false);
    }

    public function test_reviews_use_creation_period_and_surviving_rows_with_correct_rating_distribution(): void
    {
        $product = $this->makeProduct('Reviewed Product', 'Workshop');
        $ids = [];
        foreach ([5, 5, 1, 2, 3] as $rating) {
            $ids[] = $this->review($product, $rating);
        }
        $this->review($product, 2, now()->subDays(60));
        $this->review($product, 1, now()->addDay());
        $this->actingAs($this->admin());
        $response = $this->get('/admin/reports?range=7_days&sales_source=provider')->assertOk();
        $report = $response->viewData('reviewReport');

        $this->assertSame(5, $report['total']);
        $this->assertSame(3.2, $report['average']);
        $this->assertSame(2, $report['low_ratings']);
        $this->assertSame([2, 0, 1, 1, 1], array_column($report['ratings'], 'count'));
        $this->assertSame([40.0, 0.0, 20.0, 20.0, 20.0], array_map('floatval', array_column($report['ratings'], 'percent')));
        $this->assertSame([$ids[3], $ids[2]], $report['recent_low_ratings']->modelKeys());
        DB::table('product_reviews')->where('review_id', $ids[0])->delete();
        $after = $this->get('/admin/reports?range=7_days')->assertOk()->viewData('reviewReport');
        $this->assertSame(4, $after['total']);
        $this->assertSame(2.75, $after['average']);
    }

    public function test_populated_operations_are_escaped_and_localized(): void
    {
        $name = '<img src=x onerror=alert(1)>';
        $product = $this->catalogProduct($name, 0);
        $this->makeOrder($product, 1, 20, 'paid');
        $this->review($product, 1, comment: '<script>alert(2)</script>');
        $this->application('submitted', now()->subHour());

        $response = $this->actingAs($this->admin())->withSession(['locale' => 'vi'])
            ->get('/admin/reports')->assertOk();

        $response->assertSee(__('management.report_operations.title', [], 'vi'))
            ->assertSee(__('management.report_operations.available_units', [], 'vi'))
            ->assertSee(e($name), false)->assertDontSee($name, false)
            ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(2)</script>', false);
        $this->assertStringNotContainsString('management.', strip_tags($response->getContent()));
    }
}
