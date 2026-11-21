<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWalletPayments;
use Tests\TestCase;

class SimulatedWalletTest extends TestCase
{
    use CreatesWalletPayments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Http::preventStrayRequests();
        foreach (['momo', 'zalopay', 'vnpay'] as $provider) {
            config(["payments.providers.{$provider}_demo.enabled" => true]);
        }
    }

    public static function providers(): array
    {
        return [['momo_demo'], ['zalopay_demo'], ['vnpay_demo']];
    }

    #[DataProvider('providers')]
    public function test_hosted_demo_redirects_without_a_provider_account_and_keeps_authoritative_amount(string $provider): void
    {
        // Hosted demonstrations intentionally work with APP_ENV=production too.
        $this->app->instance('env', 'production');
        $order = $this->walletOrder();
        $response = $this->withSession(['_token' => 'wallet-demo-test-token'])->post(route('payments.store', $order), [
            '_token' => 'wallet-demo-test-token',
            'payment_option' => $provider, 'amount' => '0.01', 'currency' => 'VND',
        ]);
        $response->assertRedirect();
        $payment = $order->payments()->sole();
        $response->assertRedirect(route('payments.wallet-demo.show', $payment));
        $this->assertSame($provider, $payment->provider);
        $this->assertSame('49.95', $payment->amount);
        $this->assertSame('USD', $payment->currency);
        $this->get($payment->checkout_url)->assertOk()->assertSee(__('checkout.wallet.simulation_notice'));
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->post(route('payments.wallet-demo.store', $payment), [
            '_token' => 'wallet-demo-test-token', 'outcome' => 'succeeded',
        ])
            ->assertRedirect(route('orders.show', $order));
        $this->assertSame('paid', $order->fresh()->status->value);
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        Http::assertNothingSent();
    }

    public function test_repeated_initialization_reuses_the_demo_session(): void
    {
        $order = $this->walletOrder();
        $payment = $this->walletPayment('momo_demo', $order);
        $this->postJson(route('payments.store', $order), ['payment_option' => 'momo_demo'])
            ->assertCreated()->assertJsonPath('payment_id', $payment->getKey());
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('fake_payment_sessions', 1);
    }

    public function test_hosted_simulation_requires_a_csrf_token(): void
    {
        $payment = $this->walletPayment('momo_demo');
        $this->app->instance('env', 'production');
        $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => 'succeeded'])
            ->assertStatus(419);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_failed_and_cancelled_attempts_can_be_retried_without_marking_the_order_paid(): void
    {
        foreach (['failed', 'cancelled'] as $outcome) {
            $order = $this->walletOrder();
            $payment = $this->walletPayment('zalopay_demo', $order);
            $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => $outcome])
                ->assertRedirect(route('checkout.show', $order));
            $this->assertSame($outcome, $payment->fresh()->status->value);
            $this->assertSame('pending', $order->fresh()->status->value);
            $this->postJson(route('payments.store', $order), ['payment_option' => 'zalopay_demo'])->assertCreated();
            $this->assertSame(2, $order->payments()->count());
        }
    }

    public function test_delayed_payment_uses_the_existing_completion_action(): void
    {
        $payment = $this->walletPayment('vnpay_demo');
        $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => 'delayed'])->assertRedirect();
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->postJson(route('payments.complete', $payment))->assertJsonPath('status', 'processing');
        $this->travel(31)->seconds();
        $this->postJson(route('payments.complete', $payment))->assertJsonPath('status', 'succeeded');
        $this->assertSame('paid', $payment->order->fresh()->status->value);
    }

    public function test_another_customer_and_guests_cannot_use_the_simulator(): void
    {
        $payment = $this->walletPayment('momo_demo');
        $this->actingAs(User::factory()->create());
        $this->get($payment->checkout_url)->assertNotFound();
        $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => 'succeeded'])->assertNotFound();
        auth()->forgetGuards();
        $this->getJson($payment->checkout_url)->assertUnauthorized();
        $this->postJson(route('payments.wallet-demo.store', $payment), ['outcome' => 'succeeded'])->assertUnauthorized();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_disabled_simulation_rejects_new_and_existing_attempts(): void
    {
        $payment = $this->walletPayment('momo_demo');
        config(['payments.providers.momo_demo.enabled' => false]);
        $this->get($payment->checkout_url)->assertNotFound();
        $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => 'succeeded'])->assertNotFound();
        $this->postJson(route('payments.store', $payment->order), ['payment_option' => 'momo_demo'])
            ->assertUnprocessable();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_real_wallet_payments_cannot_be_finalized_through_the_demo_endpoint(): void
    {
        $this->fakeWallets();
        $payment = $this->walletPayment('momo');
        $this->get(route('payments.wallet-demo.show', $payment))->assertNotFound();
        $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => 'succeeded'])->assertNotFound();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_invalid_outcomes_and_replays_do_not_change_finalized_payments(): void
    {
        $payment = $this->walletPayment('momo_demo');
        $endpoint = route('payments.wallet-demo.store', $payment);
        $this->postJson($endpoint, ['outcome' => 'paid'])->assertUnprocessable();
        $this->post($endpoint, ['outcome' => 'succeeded'])->assertRedirect();
        $this->postJson($endpoint, ['outcome' => 'failed'])->assertUnprocessable();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(1, $payment->events()->where('event_type', 'payment_status_changed')->count());
    }

    public function test_payment_after_order_expiration_requires_refund_review(): void
    {
        $payment = $this->walletPayment('momo_demo');
        $this->travel(31)->minutes();
        $this->post(route('payments.wallet-demo.store', $payment), ['outcome' => 'succeeded'])->assertRedirect();
        $this->assertSame(PaymentStatus::RefundRequired, $payment->fresh()->status);
        $this->assertSame('expired', $payment->order->fresh()->status->value);
    }

    public function test_wallet_selector_labels_demo_options_without_replacing_paypal(): void
    {
        $order = $this->walletOrder();
        $this->blade('<x-checkout.payment-methods.wallet :order="$order" :can-create-payment="true" />', compact('order'))
            ->assertSee('value="momo_demo"', false)
            ->assertSee('value="zalopay_demo"', false)
            ->assertSee('value="vnpay_demo"', false)
            ->assertSee('value="paypal"', false)
            ->assertSee(__('checkout.wallet.simulated'));
    }
}
