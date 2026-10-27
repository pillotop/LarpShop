<?php

namespace Tests\Feature\Vendor;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createVendorWithProduct(string $storeName, string $productName, float $price): array
    {
        $owner = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $owner->user_id,
            'store_name' => $storeName.' '.uniqid(),
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Cat '.uniqid(),
            'slug' => 'cat-'.uniqid(),
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => $productName,
            'slug' => 'prod-'.uniqid(),
            'price' => $price,
            'stock' => 20,
        ]);

        return [$owner, $vendor, $product];
    }

    public function test_vendor_only_sees_orders_containing_their_products(): void
    {
        [$ownerA, , $productA] = $this->createVendorWithProduct('Alpha Forge', 'Alpha Broadsword', 50.00);
        [, , $productB] = $this->createVendorWithProduct('Beta Leather', 'Beta Boots', 40.00);

        $buyer = User::factory()->create();

        $orderForA = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 50.00,
            'status' => OrderStatus::Paid,
        ]);
        $orderForA->details()->create([
            'product_id' => $productA->product_id,
            'quantity' => 1,
            'unit_price' => 50.00,
        ]);

        $orderForBOnly = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 40.00,
            'status' => OrderStatus::Paid,
        ]);
        $orderForBOnly->details()->create([
            'product_id' => $productB->product_id,
            'quantity' => 1,
            'unit_price' => 40.00,
        ]);

        $this->actingAs($ownerA)
            ->get(route('vendor.orders.index'))
            ->assertOk()
            ->assertSee('Alpha Broadsword')
            ->assertDontSee('Beta Boots');

        // Vendor A cannot access an order that has no items from Vendor A
        $this->actingAs($ownerA)
            ->get(route('vendor.orders.show', $orderForBOnly->order_id))
            ->assertNotFound();
    }

    public function test_multi_vendor_order_isolates_items_and_subtotal_per_vendor(): void
    {
        [$ownerA, , $productA] = $this->createVendorWithProduct('Alpha Forge', 'Alpha Greatsword', 60.00);
        [, , $productB] = $this->createVendorWithProduct('Beta Armory', 'Beta Secret Shield', 85.00);

        $buyer = User::factory()->create(['username' => 'knight_buyer']);

        $sharedOrder = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 205.00, // (2 * 60) + (1 * 85)
            'status' => OrderStatus::Paid,
        ]);

        $sharedOrder->details()->create([
            'product_id' => $productA->product_id,
            'quantity' => 2,
            'unit_price' => 60.00,
        ]);
        $sharedOrder->details()->create([
            'product_id' => $productB->product_id,
            'quantity' => 1,
            'unit_price' => 85.00,
        ]);

        $response = $this->actingAs($ownerA)
            ->get(route('vendor.orders.show', $sharedOrder->order_id))
            ->assertOk();

        $response->assertSee('Alpha Greatsword');
        $response->assertSee('120.00'); // Vendor A's exact subtotal (2 * $60)
        $response->assertDontSee('Beta Secret Shield');
    }

    public function test_vendor_can_progress_fulfillment_status_on_paid_order_and_preserves_order_status(): void
    {
        [$ownerA, , $productA] = $this->createVendorWithProduct('Alpha Forge', 'Alpha Helm', 45.00);
        [, , $productB] = $this->createVendorWithProduct('Beta Armory', 'Beta Gauntlets', 30.00);

        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 75.00,
            'status' => OrderStatus::Paid,
        ]);

        $detailA = $order->details()->create([
            'product_id' => $productA->product_id,
            'quantity' => 1,
            'unit_price' => 45.00,
        ]);
        $detailB = $order->details()->create([
            'product_id' => $productB->product_id,
            'quantity' => 1,
            'unit_price' => 30.00,
        ]);

        // 1. Transition to Processing
        $this->actingAs($ownerA)
            ->patch(route('vendor.orders.fulfillment.update', $order->order_id), [
                'order_detail_id' => $detailA->order_detail_id,
                'fulfillment_status' => FulfillmentStatus::Processing->value,
            ])
            ->assertRedirect(route('vendor.orders.show', $order->order_id))
            ->assertSessionHas('success');

        $this->assertSame(FulfillmentStatus::Processing, $detailA->fresh()->fulfillment_status);
        $this->assertNull($detailA->fresh()->shipped_at);

        // 2. Transition to Shipped
        $this->actingAs($ownerA)
            ->patch(route('vendor.orders.fulfillment.update', $order->order_id), [
                'order_detail_id' => $detailA->order_detail_id,
                'fulfillment_status' => FulfillmentStatus::Shipped->value,
            ])
            ->assertRedirect(route('vendor.orders.show', $order->order_id));

        $this->assertSame(FulfillmentStatus::Shipped, $detailA->fresh()->fulfillment_status);
        $this->assertNotNull($detailA->fresh()->shipped_at);

        // 3. Transition to Delivered
        $this->actingAs($ownerA)
            ->patch(route('vendor.orders.fulfillment.update', $order->order_id), [
                'order_detail_id' => $detailA->order_detail_id,
                'fulfillment_status' => FulfillmentStatus::Delivered->value,
            ])
            ->assertRedirect(route('vendor.orders.show', $order->order_id));

        $detailA->refresh();
        $this->assertSame(FulfillmentStatus::Delivered, $detailA->fulfillment_status);
        $this->assertNotNull($detailA->delivered_at);

        // Order lifecycle status remains Paid (docs/ORDER_STATUS_MODEL.md), and Vendor B's item remains Unfulfilled
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(FulfillmentStatus::Unfulfilled, $detailB->fresh()->fulfillment_status);
    }

    public function test_vendor_cannot_update_fulfillment_for_another_vendors_order_line(): void
    {
        [$ownerA, , $productA] = $this->createVendorWithProduct('Alpha Forge', 'Alpha Spear', 55.00);
        [, , $productB] = $this->createVendorWithProduct('Beta Armory', 'Beta Halberd', 70.00);

        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 125.00,
            'status' => OrderStatus::Paid,
        ]);

        $order->details()->create([
            'product_id' => $productA->product_id,
            'quantity' => 1,
            'unit_price' => 55.00,
        ]);
        $detailB = $order->details()->create([
            'product_id' => $productB->product_id,
            'quantity' => 1,
            'unit_price' => 70.00,
        ]);

        $this->actingAs($ownerA)
            ->patch(route('vendor.orders.fulfillment.update', $order->order_id), [
                'order_detail_id' => $detailB->order_detail_id,
                'fulfillment_status' => FulfillmentStatus::Shipped->value,
            ])
            ->assertForbidden();

        $this->assertSame(FulfillmentStatus::Unfulfilled, $detailB->fresh()->fulfillment_status);
    }

    public function test_vendor_cannot_fulfill_pending_or_cancelled_orders(): void
    {
        [$ownerA, , $productA] = $this->createVendorWithProduct('Alpha Forge', 'Alpha Bow', 65.00);
        $buyer = User::factory()->create();

        $pendingOrder = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 65.00,
            'status' => OrderStatus::Pending,
        ]);
        $detail = $pendingOrder->details()->create([
            'product_id' => $productA->product_id,
            'quantity' => 1,
            'unit_price' => 65.00,
        ]);

        $this->actingAs($ownerA)
            ->patch(route('vendor.orders.fulfillment.update', $pendingOrder->order_id), [
                'fulfillment_status' => FulfillmentStatus::Processing->value,
            ])
            ->assertSessionHasErrors('fulfillment_status');

        $this->assertSame(FulfillmentStatus::Unfulfilled, $detail->fresh()->fulfillment_status);
    }

    public function test_backward_fulfillment_status_transition_is_rejected(): void
    {
        [$ownerA, , $productA] = $this->createVendorWithProduct('Alpha Forge', 'Alpha Crossbow', 95.00);
        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id' => $buyer->user_id,
            'total_amount' => 95.00,
            'status' => OrderStatus::Paid,
        ]);
        $detail = $order->details()->create([
            'product_id' => $productA->product_id,
            'quantity' => 1,
            'unit_price' => 95.00,
            'fulfillment_status' => FulfillmentStatus::Shipped,
            'shipped_at' => now(),
        ]);

        $this->actingAs($ownerA)
            ->patch(route('vendor.orders.fulfillment.update', $order->order_id), [
                'order_detail_id' => $detail->order_detail_id,
                'fulfillment_status' => FulfillmentStatus::Processing->value,
            ])
            ->assertSessionHasErrors('fulfillment_status');

        $this->assertSame(FulfillmentStatus::Shipped, $detail->fresh()->fulfillment_status);
    }
}
