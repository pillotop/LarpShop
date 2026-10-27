<?php

namespace Tests\Feature\Payments;

use App\Models\FakePaymentSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return Order::create(['user_id' => $user->getKey(), 'total_amount' => '49.95', 'status' => 'pending']);
    }

    private function payment(Order $order): Payment
    {
        $id = $this->postJson(route('test.payments.store', $order))->assertCreated()->json('payment_id');

        return Payment::findOrFail($id);
    }

    public function test_client_cannot_override_authoritative_payment_values(): void
    {
        $order = $this->order();
        $other = User::factory()->create();
        $response = $this->postJson(route('test.payments.store', $order), [
            'amount' => '0.01', 'total_amount' => '0.01', 'currency' => 'INVALID',
            'status' => 'succeeded', 'user_id' => $other->getKey(), 'order_id' => 999999,
        ])->assertCreated()->assertJsonPath('status', 'pending');
        $payment = Payment::findOrFail($response->json('payment_id'));
        $this->assertSame('49.95', $payment->amount);
        $this->assertSame(config('payments.currency'), $payment->currency);
        $this->assertEquals($order->getKey(), $payment->order_id);
        $this->assertSame('pending', $order->fresh()->status->value);
        $this->assertNull($payment->completed_at);
    }

    public function test_repeated_requests_create_only_one_active_attempt(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->postJson(route('test.payments.store', $order))->assertCreated()->assertJsonPath('payment_id', $payment->getKey());
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('fake_payment_sessions', 1);
        $this->assertSame(1, $payment->events()->count());
    }

    public function test_other_customers_cannot_create_simulate_or_verify_payments(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $before = $payment->toArray();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('test.payments.store', $order))->assertNotFound();
        $this->postJson(route('test.payments.simulate', $payment), ['outcome' => 'succeeded'])->assertNotFound();
        $this->postJson(route('test.payments.verify', $payment))->assertNotFound();
        $this->assertSame($before, $payment->fresh()->toArray());
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(1, $payment->events()->count());
        $this->assertDatabaseHas('fake_payment_sessions', ['reference' => $payment->provider_reference, 'status' => 'pending']);
        $this->assertSame('pending', $order->fresh()->status->value);
    }

    #[DataProvider('terminalStatuses')]
    public function test_finalized_attempts_reject_conflicting_provider_results(string $status): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $payment->update(['status' => $status, 'completed_at' => now()]);
        $order->update(['status' => $status === 'succeeded' ? 'paid' : 'pending']);
        FakePaymentSession::where('reference', $payment->provider_reference)->update([
            'status' => $status === 'succeeded' ? 'failed' : 'succeeded',
        ]);
        $before = $payment->fresh()->toArray();
        $this->postJson(route('test.payments.verify', $payment))
            ->assertUnprocessable()->assertJsonValidationErrors('payment');
        $this->assertSame($before, $payment->fresh()->toArray());
        $this->assertSame(1, $payment->events()->count());
        $this->assertSame($status === 'succeeded' ? 'paid' : 'pending', $order->fresh()->status->value);
    }

    public static function terminalStatuses(): array
    {
        return array_map(fn ($status) => [$status], ['succeeded', 'failed', 'cancelled', 'refunded']);
    }

    public function test_failed_attempt_can_be_retried_and_new_attempt_can_succeed(): void
    {
        $order = $this->order();
        $failed = $this->payment($order);
        $this->postJson(route('test.payments.simulate', $failed), ['outcome' => 'failed'])->assertOk()->assertJsonPath('status', 'failed');
        $this->assertSame('pending', $order->fresh()->status->value);
        $retry = $this->payment($order);
        $this->assertNotEquals($failed->getKey(), $retry->getKey());
        $this->assertNotSame($failed->provider_reference, $retry->provider_reference);
        $this->postJson(route('test.payments.simulate', $retry), ['outcome' => 'succeeded'])->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertSame('failed', $failed->fresh()->status->value);
        $this->assertSame('49.95', $retry->fresh()->amount);
        $this->assertSame('paid', $order->fresh()->status->value);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(2, $failed->events()->count());
        $this->assertSame(2, $retry->events()->count());
        $this->assertNotNull($failed->fresh()->completed_at);
        $this->assertNotNull($retry->fresh()->completed_at);
    }

    public function test_delayed_attempt_eventually_succeeds_and_verification_is_idempotent(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->postJson(route('test.payments.verify', $payment))->assertOk()->assertJsonPath('status', 'pending');
        $this->assertSame(1, $payment->events()->count());
        $this->postJson(route('test.payments.simulate', $payment), ['outcome' => 'delayed'])->assertOk()->assertJsonPath('status', 'processing');
        $this->assertNull($payment->fresh()->completed_at);
        $this->assertSame('pending', $order->fresh()->status->value);
        $this->postJson(route('test.payments.store', $order))->assertUnprocessable();
        $this->postJson(route('test.payments.verify', $payment))->assertOk()->assertJsonPath('status', 'processing');
        $this->assertSame(2, $payment->events()->count());
        $this->travel(31)->seconds();
        $this->postJson(route('test.payments.verify', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertSame('succeeded', $payment->fresh()->status->value);
        $this->assertSame('paid', $order->fresh()->status->value);
        $this->postJson(route('test.payments.verify', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->postJson(route('test.payments.simulate', $payment), ['outcome' => 'failed'])->assertUnprocessable();
        $this->assertSame(3, $payment->events()->count());
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('fake_payment_sessions', ['reference' => $payment->provider_reference, 'status' => 'succeeded', 'ready_at' => null]);
        $this->assertNotNull($payment->fresh()->completed_at);
    }
}
