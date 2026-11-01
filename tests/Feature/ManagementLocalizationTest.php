<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class ManagementLocalizationTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
    }

    public static function languages(): array
    {
        return [['en'], ['vi']];
    }

    #[DataProvider('languages')]
    public function test_admin_pages_render_localized_labels_and_real_settings_drawers(string $locale): void
    {
        $product = $this->product();
        $admin = $product->vendor->user;
        $admin->update(['role' => 'admin']);
        $order = $this->order($product);
        $this->actingAs($admin)->withSession(['locale' => $locale]);

        $pages = [
            ['admin.dashboard', [], 'management.admin_dashboard'],
            ['admin.vendors.index', [], 'management.store_approval_and_management'],
            ['admin.vendors.edit', [$product->vendor], 'management.store_configuration'],
            ['admin.categories.index', [], 'management.category_management'],
            ['admin.users.index', [], 'management.user_management'],
            ['admin.orders.index', [], 'management.order_investigation'],
            ['admin.orders.show', [$order], 'management.recorded_total'],
            ['admin.products.moderation.index', [], 'management.product_moderation'],
            ['admin.reviews.index', [], 'management.review_moderation'],
            ['admin.reports.index', [], 'management.platform_overview'],
        ];

        foreach ($pages as [$route, $parameters, $key]) {
            $response = $this->get(route($route, $parameters))->assertOk()
                ->assertSee('<html lang="'.$locale.'">', false)
                ->assertSee(__($key, [], $locale))
                ->assertSee('<dialog', false)
                ->assertSee('id="settings-panel"', false)
                ->assertSee('name="locale"', false);
            $this->assertSame(1, substr_count($response->getContent(), '<aside class="admin-sidebar'));
            $this->assertStringNotContainsString('management.', strip_tags($response->getContent()));
        }

        $this->get(route('vendor.categories.edit', $product->category))->assertOk()
            ->assertSee(__('management.update_category', [], $locale));
    }

    #[DataProvider('languages')]
    public function test_vendor_pages_keep_store_data_and_internal_form_values(string $locale): void
    {
        $product = $this->product('Original Sword');
        $owner = $product->vendor->user;
        $order = $this->order($product);
        $this->actingAs($owner)->withSession(['locale' => $locale]);

        $pages = [
            ['vendor.store.settings', [], 'management.store_settings'],
            ['vendor.products.index', [], 'management.products_inventory'],
            ['vendor.products.create', [], 'management.create_product_listing'],
            ['vendor.products.edit', [$product], 'management.edit_product_listing'],
            ['vendor.products.images.index', [$product], 'management.manage_product_images'],
            ['vendor.products.variants.index', [$product], 'management.manage_product_variants'],
            ['vendor.categories.index', [], 'management.platform_categories'],
            ['vendor.categories.create', [], 'management.create_category'],
            ['vendor.orders.index', [], 'management.store_orders'],
            ['vendor.orders.show', [$order], 'management.managed_by'],
            ['vendor.reports.index', [], 'management.sales_inventory_reports'],
        ];

        foreach ($pages as [$route, $parameters, $key]) {
            $response = $this->get(route($route, $parameters))->assertOk()
                ->assertSee('<html lang="'.$locale.'">', false);
            if ($key !== 'management.managed_by') {
                $response->assertSee(__($key, [], $locale));
            }
            $this->assertStringNotContainsString('management.', strip_tags($response->getContent()));
        }

        $this->get(route('vendor.products.index'))->assertOk()
            ->assertSee(__('management.availability.visible', [], $locale))
            ->assertSee('value="visible"', false);
        $this->get(route('vendor.products.edit', $product))->assertOk()
            ->assertSee(__('management.update_product_listing', [], $locale));
        $this->get(route('vendor.orders.show', $order))->assertOk()
            ->assertSee('value="shipped"', false)
            ->assertSee(__('management.fulfillment_labels.shipped', [], $locale));
        $this->assertSame('paid', $order->fresh()->status->value);
        $this->assertSame('unfulfilled', $order->details()->first()->fulfillment_status->value);
    }

    public function test_localized_vendor_actions_keep_validation_and_ownership_checks(): void
    {
        $product = $this->product();
        $this->actingAs($product->vendor->user)->withSession(['locale' => 'vi']);

        $this->postJson(route('vendor.products.store'), [])->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Trường tên là bắt buộc.');
        $this->patch(route('vendor.products.update-stock', $product), ['stock' => 7])
            ->assertRedirect()
            ->assertSessionHas('success', "Đã cập nhật tồn kho của 'Test Sword' thành 7.");
        $this->assertSame(7, $product->fresh()->stock);

        $stranger = User::factory()->create();
        $stranger->vendors()->create(['store_name' => 'Other store', 'status' => 'active']);
        $this->actingAs($stranger)->withSession(['locale' => 'vi'])
            ->patchJson(route('vendor.products.update-stock', $product), ['stock' => 99])
            ->assertForbidden()
            ->assertJsonPath('message', 'Bạn không có quyền thực hiện thao tác này.');
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_fulfillment_labels_do_not_change_enum_values(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Đã gửi', FulfillmentStatus::Shipped->label());
        $this->assertSame('shipped', FulfillmentStatus::Shipped->value);
        $this->assertTrue(FulfillmentStatus::Processing->canTransitionTo(FulfillmentStatus::Shipped));
        $this->assertFalse(FulfillmentStatus::Shipped->canTransitionTo(FulfillmentStatus::Unfulfilled));
    }

    private function order(Product $product): Order
    {
        $order = Order::create([
            'user_id' => User::factory()->create()->getKey(),
            'total_amount' => $product->price,
            'status' => OrderStatus::Paid,
        ]);
        $order->details()->create([
            'product_id' => $product->getKey(), 'quantity' => 1, 'unit_price' => $product->price,
        ]);

        return $order;
    }
}
