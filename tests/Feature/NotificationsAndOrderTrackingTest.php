<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Vendors\VendorOrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class NotificationsAndOrderTrackingTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_vendor_fulfillment_update_notifies_buyer_and_bell_marks_read(): void
    {
        $this->withoutVite();

        $buyer = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->product(name: 'Obsidian Gauntlets');
        $vendor = $product->vendor;

        $order = $buyer->orders()->create([
            'status' => OrderStatus::Paid,
            'total_amount' => '25.00',
            'subtotal_amount' => '25.00',
        ]);
        $order->details()->create([
            'product_id' => $product->getKey(),
            'quantity' => 2,
            'unit_price' => '12.50',
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        ]);

        app(VendorOrderFulfillmentService::class)->updateFulfillment(
            $vendor,
            $order,
            FulfillmentStatus::Shipped,
        );

        $this->assertDatabaseCount('notifications', 1);
        $notification = $buyer->unreadNotifications()->firstOrFail();

        $this->actingAs($buyer)->get(route('home'))
            ->assertOk()
            ->assertSee('data-notification-bell', false)
            ->assertSee('data-notification-badge', false)
            ->assertSee("Order #{$order->getKey()}");

        // Another user cannot read someone else's notification.
        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->actingAs($buyer)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(0, $buyer->unreadNotifications()->count());

        // Mark-all endpoint clears unread notifications.
        app(VendorOrderFulfillmentService::class)->updateFulfillment(
            $vendor,
            $order,
            FulfillmentStatus::Delivered,
        );
        $this->assertSame(1, $buyer->unreadNotifications()->count());

        $this->actingAs($buyer)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_order_detail_shows_timeline_and_buyer_can_confirm_shipped_delivery(): void
    {
        $this->withoutVite();

        $buyer = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->product(name: 'Ranger Cloak');

        $order = $buyer->orders()->create([
            'status' => OrderStatus::Paid,
            'total_amount' => '50.00',
            'subtotal_amount' => '50.00',
        ]);
        $detail = $order->details()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'unit_price' => '50.00',
            'fulfillment_status' => FulfillmentStatus::Shipped,
            'shipped_at' => now()->subHour(),
        ]);

        $this->actingAs($buyer)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Order Tracking')
            ->assertSee('Confirm order received')
            ->assertSee('Ranger Cloak');

        // Another user cannot confirm receipt.
        $this->actingAs($otherUser)
            ->post(route('orders.confirm-delivery', $order))
            ->assertNotFound();

        $this->actingAs($buyer)
            ->post(route('orders.confirm-delivery', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status', __('orders.delivery_confirmed'));

        $detail->refresh();
        $this->assertSame(FulfillmentStatus::Delivered, $detail->fulfillment_status);
        $this->assertNotNull($detail->delivered_at);
    }

    public function test_unshipped_order_cannot_be_confirmed_as_delivered(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $order = $buyer->orders()->create([
            'status' => OrderStatus::Paid,
            'total_amount' => '12.50',
        ]);
        $order->details()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'unit_price' => '12.50',
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        ]);

        $this->actingAs($buyer)
            ->from(route('orders.show', $order))
            ->post(route('orders.confirm-delivery', $order))
            ->assertSessionHasErrors('order');
    }

    public function test_payment_succeeded_notification_supports_database_channel(): void
    {
        $buyer = User::factory()->create();
        $order = $buyer->orders()->create([
            'status' => OrderStatus::Paid,
            'total_amount' => '35.00',
        ]);
        $payment = Payment::create([
            'order_id' => $order->getKey(),
            'provider' => 'fake',
            'method' => PaymentMethod::Card,
            'amount' => '35.00',
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded,
        ]);

        $notification = new PaymentSucceededNotification($payment);
        $this->assertSame(['mail', 'database'], $notification->via($buyer));

        $payload = $notification->toArray($buyer);
        $this->assertSame(route('orders.show', $order), $payload['url']);
        $this->assertStringContainsString((string) $order->getKey(), $payload['message']);
    }
}
