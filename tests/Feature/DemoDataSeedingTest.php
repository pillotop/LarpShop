<?php

namespace Tests\Feature;

use App\Data\Reports\ReportPeriod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PromoSource;
use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\PromoSection;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Queries\Products\ProductCatalogQuery;
use App\Queries\Reports\CompletedSaleQuery;
use App\Queries\Reports\FulfillmentReportQuery;
use App\Queries\Reports\InventoryReportQuery;
use App\Queries\Reports\PaymentReportQuery;
use App\Queries\Reports\PlatformReportQuery;
use App\Queries\Reports\PlatformTrendQuery;
use App\Queries\Reports\ReviewReportQuery;
use App\Queries\Vendors\VendorAnalyticsQuery;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_idempotent_and_satisfies_consistency_and_report_coverage(): void
    {
        Storage::fake('public');
        $this->registerSqliteDateFormatIfNeeded();

        $existingUnverified = User::factory()->unverified()->create([
            'username' => 'preexisting_unverified_real_user',
            'email' => 'preexisting@example.org',
        ]);

        $this->seed(DatabaseSeeder::class);

        $snapshot1 = $this->tableSnapshot();
        $productStocks1 = Product::query()->orderBy('product_id')->pluck('stock', 'product_id')->all();
        $variantStocks1 = ProductVariant::query()->orderBy('variant_id')->pluck('stock', 'variant_id')->all();

        // Re-running DatabaseSeeder must not create duplicates or decrement inventory again.
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($snapshot1, $this->tableSnapshot(), 'Seeder must remain count-idempotent on re-run.');
        $this->assertSame($productStocks1, Product::query()->orderBy('product_id')->pluck('stock', 'product_id')->all());
        $this->assertSame($variantStocks1, ProductVariant::query()->orderBy('variant_id')->pluck('stock', 'variant_id')->all());

        // Pre-existing user must never be mass-verified or modified.
        $this->assertNull($existingUnverified->fresh()->email_verified_at);

        // Scale & store requirements.
        $this->assertGreaterThanOrEqual(40, User::where('role', 'customer')->count());
        $this->assertGreaterThanOrEqual(8, Vendor::count());
        $this->assertGreaterThanOrEqual(150, Product::count());
        $this->assertGreaterThanOrEqual(250, Order::count());
        $this->assertGreaterThan(Order::count(), Payment::count());
        $this->assertGreaterThanOrEqual(150, ProductReview::count());

        $multiOwner = User::where('username', 'volttech')->firstOrFail();
        $this->assertGreaterThanOrEqual(2, $multiOwner->vendors()->count());

        foreach (VendorApplicationStatus::cases() as $appStatus) {
            $this->assertTrue(VendorApplication::where('status', $appStatus->value)->exists());
        }
        $this->assertTrue(Vendor::where('status', 'pending')->whereDoesntHave('application')->exists());

        // Storefront promotions & Top Rated candidates.
        $this->assertGreaterThan(0, ProductCollection::published()->count());
        $this->assertGreaterThan(0, PromoSection::active()->count());
        $this->assertTrue(app(ProductCatalogQuery::class)->automaticPromoCandidates(PromoSource::TopRated)->exists());

        // Local assets only, no external hotlinks or auth carousel assets.
        $imagePaths = ProductImage::query()->pluck('path');
        $this->assertNotEmpty($imagePaths);
        foreach ($imagePaths as $path) {
            $this->assertFalse(str_starts_with($path, 'http://') || str_starts_with($path, 'https://'));
            $this->assertFalse(str_contains($path, 'images/auth'));
            $this->assertTrue(Storage::disk('public')->exists($path), "Missing local asset: {$path}");
        }

        // Cancelled / expired orders must never be fulfilled as delivered.
        $badFulfillment = OrderDetail::query()
            ->whereHas('order', fn ($q) => $q->whereIn('status', [OrderStatus::Cancelled->value, OrderStatus::Expired->value]))
            ->where('fulfillment_status', FulfillmentStatus::Delivered->value)
            ->exists();
        $this->assertFalse($badFulfillment);

        // Every review belongs to a customer with a Paid order for that product and occurs after purchase.
        $reviews = ProductReview::query()->get();
        foreach ($reviews as $review) {
            $purchasedAt = Payment::query()
                ->join('orders', 'orders.order_id', '=', 'payments.order_id')
                ->join('order_details', 'order_details.order_id', '=', 'orders.order_id')
                ->where('orders.user_id', $review->user_id)
                ->where('orders.status', OrderStatus::Paid->value)
                ->where('order_details.product_id', $review->product_id)
                ->where('payments.status', PaymentStatus::Succeeded->value)
                ->min('payments.completed_at');

            $this->assertNotNull($purchasedAt, "Review {$review->review_id} lacks matching Paid order.");
            $this->assertTrue($review->created_at->greaterThan($purchasedAt));
        }

        // Leave some approved products unreviewed.
        $this->assertTrue(Product::approved()->doesntHave('reviews')->exists());

        // Report coverage assertions across official report query classes.
        $this->assertReportCoverage();
    }

    private function registerSqliteDateFormatIfNeeded(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function (?string $value, string $format): ?string {
            if ($value === null) {
                return null;
            }
            $phpFormat = str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], $format);

            return Carbon::parse($value)->format($phpFormat);
        }, 2);
    }

    /**
     * @return array<string, int>
     */
    private function tableSnapshot(): array
    {
        return [
            'users' => User::count(),
            'vendors' => Vendor::count(),
            'vendor_applications' => VendorApplication::count(),
            'products' => Product::count(),
            'product_variants' => ProductVariant::count(),
            'product_images' => ProductImage::count(),
            'orders' => Order::count(),
            'order_details' => OrderDetail::count(),
            'order_addresses' => OrderAddress::count(),
            'payments' => Payment::count(),
            'reviews' => ProductReview::count(),
            'addresses' => Address::count(),
            'cart_items' => CartItem::count(),
            'wishlist_items' => DB::table('wishlist_items')->count(),
        ];
    }

    private function assertReportCoverage(): void
    {
        $period7 = ReportPeriod::forRange('7_days');
        $period30 = ReportPeriod::forRange('30_days');
        $periodMonth = ReportPeriod::forRange('this_month');
        $periodAll = ReportPeriod::forRange('all');

        $salesQuery = app(CompletedSaleQuery::class);
        $this->assertGreaterThan(0, $salesQuery->summary($period7, 'all')['gross_revenue']);
        $this->assertGreaterThan(0, $salesQuery->summary($periodMonth, 'all')['gross_revenue']);
        $this->assertGreaterThan(0, $salesQuery->summary($period30, 'provider')['gross_revenue']);
        $this->assertGreaterThan(0, $salesQuery->summary($period30, 'simulated')['gross_revenue']);
        $this->assertGreaterThan(0, $salesQuery->summary($period30->previous(), 'all')['paid_orders']);

        $undatedSuccesses = Payment::query()
            ->where('status', PaymentStatus::Succeeded->value)
            ->whereNull('completed_at')
            ->count();
        $this->assertSame(0, $undatedSuccesses);

        $trendQuery = app(PlatformTrendQuery::class);
        $this->assertNotEmpty($trendQuery->forPeriod($period30, 'all'));
        $this->assertNotEmpty($trendQuery->forPeriod($periodAll, 'all'));

        $paymentReport = app(PaymentReportQuery::class)->overview($period30);
        $this->assertNotEmpty($paymentReport['providers']);

        $inventoryReport = app(InventoryReportQuery::class)->overview($periodAll);
        $this->assertGreaterThan(0, $inventoryReport['bands']['out_of_stock']);
        $this->assertGreaterThan(0, $inventoryReport['bands']['low_stock']);
        $this->assertGreaterThan(0, $inventoryReport['bands']['healthy_stock']);
        $this->assertGreaterThan(0, $inventoryReport['pending_products']);

        $fulfillmentReport = app(FulfillmentReportQuery::class)->overview($periodAll);
        $this->assertGreaterThan(0, $fulfillmentReport['awaiting_dispatch']);
        $this->assertNotEmpty($fulfillmentReport['oldest_lines']);

        $reviewReport = app(ReviewReportQuery::class)->overview($periodAll);
        $this->assertGreaterThan(0, $reviewReport['low_ratings']);
        $this->assertNotEmpty($reviewReport['recent_low_ratings']);

        // VendorApplicationReportQuery uses MySQL-specific TIMESTAMPDIFF; run full overview on MySQL.
        if (DB::connection()->getDriverName() === 'mysql') {
            $platform = app(PlatformReportQuery::class)->overview($period30, 'all');
            $this->assertGreaterThan(0, $platform['applicationReport']['pending_now']);
            $this->assertGreaterThan(0, $platform['applicationReport']['legacy_pending']);
        }

        $activeVendor = Vendor::where('store_name', 'VoltTech Gadgets')->firstOrFail();
        $vendorStats = app(VendorAnalyticsQuery::class)->getOverview($activeVendor, 'all');
        $this->assertGreaterThan(0, $vendorStats['metrics']['gross_revenue']);
    }
}
