<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    private function reservedOrder(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->product('Sword '.$user->getKey(), stock: 8);
        $order = Order::create(['user_id' => $user->getKey(), 'status' => 'pending', 'total_amount' => '25.00', 'expires_at' => now()->addMinutes(30)]);
        $order->details()->create(['product_id' => $product->getKey(), 'quantity' => 2, 'unit_price' => '12.50']);

        return [$order, $product];
    }

    private function payment(Order $order): Payment
    {
        $id = $this->postJson(route('test.payments.store', $order))->assertCreated()->json('payment_id');

        return Payment::findOrFail($id);
    }

    public function test_checkout_persists_expiration_and_reserves_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->cartWith($user, [[$product, 2]]);
        $address = $user->addresses()->create(['recipient_name' => 'Buyer', 'address_line_1' => '123 Test Street', 'city' => 'Test City', 'country_code' => 'VN']);
        $id = $this->actingAs($user)->postJson(route('checkout.store'), ['address_id' => $address->getKey()])->assertCreated()->json('order_id');
        $order = Order::findOrFail($id);
        $this->assertNotNull($order->expires_at);
        $this->assertTrue($order->expires_at->isFuture());
        $this->assertNull($order->stock_released_at);
        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_cancel_is_idempotent_and_redirects_to_an_owned_get_endpoint(): void
    {
        [$order, $product] = $this->reservedOrder();
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));
            $this->assertEquals(10, $product->fresh()->stock);
        }
        $this->getJson(route('orders.show', $order))->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertNotNull($order->fresh()->stock_released_at);
        $this->actingAs(User::factory()->create());
        $this->getJson(route('orders.show', $order))->assertNotFound();
        $this->postJson(route('orders.cancel', $order))->assertNotFound();
    }

    public function test_expiration_command_releases_only_due_pending_orders_once(): void
    {
        [$due, $product] = $this->reservedOrder();
        $due->update(['expires_at' => now()->subSecond()]);
        [$future, $futureProduct] = $this->reservedOrder();
        [$paid, $paidProduct] = $this->reservedOrder();
        $paid->update(['status' => OrderStatus::Paid, 'expires_at' => now()->subSecond()]);
        for ($i = 0; $i < 2; $i++) {
            $this->artisan('orders:expire')->assertSuccessful();
        }
        $this->assertSame(OrderStatus::Expired, $due->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertSame(OrderStatus::Pending, $future->fresh()->status);
        $this->assertEquals(8, $futureProduct->fresh()->stock);
        $this->assertSame(OrderStatus::Paid, $paid->fresh()->status);
        $this->assertEquals(8, $paidProduct->fresh()->stock);
    }

    #[DataProvider('unpayableStates')]
    public function test_new_payment_is_rejected_for_unpayable_orders(string $state): void
    {
        [$order] = $this->reservedOrder();
        if ($state === 'overdue') {
            $order->update(['expires_at' => now()->subSecond()]);
        } else {
            $order->update(['status' => $state]);
        }
        $this->postJson(route('test.payments.store', $order))->assertUnprocessable()->assertJsonValidationErrors('order');
        $this->assertDatabaseCount('payments', 0);
    }

    public static function unpayableStates(): array
    {
        return [['overdue'], ['cancelled'], ['expired'], ['paid']];
    }

    #[DataProvider('lateSuccessStates')]
    public function test_late_success_requires_refund_without_reclaiming_released_stock(string $state): void
    {
        [$order, $product] = $this->reservedOrder();
        $payment = $this->payment($order);
        if ($state === 'cancelled') {
            app(OrderLifecycleService::class)->cancel($order);
        } else {
            $order->update(['expires_at' => now()->subSecond()]);
            if ($state === 'expired') {
                app(OrderLifecycleService::class)->expire($order);
            }
        }
        $this->postJson(route('test.payments.simulate', $payment), ['outcome' => 'succeeded'])
            ->assertOk()->assertJsonPath('status', 'refund_required');
        $this->assertSame(PaymentStatus::RefundRequired, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->completed_at);
        $this->assertSame($state === 'cancelled' ? OrderStatus::Cancelled : OrderStatus::Expired, $order->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock);
        $this->postJson(route('test.payments.verify', $payment))->assertOk()->assertJsonPath('status', 'refund_required');
        $this->assertSame(2, $payment->events()->count());
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public static function lateSuccessStates(): array
    {
        return [['cancelled'], ['expired'], ['overdue']];
    }

    public function test_successful_payment_prevents_cancellation_and_keeps_stock_reserved(): void
    {
        [$order, $product] = $this->reservedOrder();
        $payment = $this->payment($order);
        $this->postJson(route('test.payments.simulate', $payment), ['outcome' => 'succeeded'])->assertOk();
        $this->postJson(route('orders.cancel', $order))->assertUnprocessable();
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_released_at);
        $this->assertEquals(8, $product->fresh()->stock);
    }
}
