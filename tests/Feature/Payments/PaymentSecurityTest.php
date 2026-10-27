<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_guest_cannot_create_payment(): void
    {
        $response = $this->post(
            route('test.payments.store', ['order' => 1])
        );

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_pay_another_users_order(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $order = Order::create([
            'user_id' => $owner->user_id,
            'status' => 'pending',
            'total_amount' => '49.95',
        ]);

        $response = $this
            ->actingAs($attacker)
            ->post(route('test.payments.store', [
                'order' => $order->order_id,
            ]));

        $response->assertNotFound();
        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->order_id,
        ]);
    }
}
