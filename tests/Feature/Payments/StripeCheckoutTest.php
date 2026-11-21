<?php

namespace Tests\Feature\Payments;

use App\Data\Payments\PaymentRequest;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Services\Payments\StripePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesStripePayments;
use Tests\TestCase;

class StripeCheckoutTest extends TestCase
{
    use CreatesStripePayments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeStripe();
    }

    public function test_creation_uses_authoritative_total_minor_units_and_stable_request_key(): void
    {
        $order = $this->stripeOrder();
        $id = $this->postJson(route('payments.store', $order), [
            'payment_option' => 'stripe_card', 'amount' => '0.01', 'currency' => 'VND', 'provider' => 'fake',
        ])->assertCreated()->json('payment_id');
        $request = $this->stripeRequests[0];
        $this->assertSame(4995, $request['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('usd', $request['line_items'][0]['price_data']['currency']);
        $this->assertSame(['card'], $request['payment_method_types']);
        $this->assertSame('false', $request['adaptive_pricing']['enabled']);
        $this->assertSame('larpshop-payment-'.$id, $request->header('Idempotency-Key')[0]);
        $this->assertSame(route('payments.return', $id), $request['success_url']);
        $this->assertSame(route('payments.cancel', $id), $request['cancel_url']);
        $this->stripePayment($order);
        $this->assertCount(1, $this->stripeRequests);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_checkout_redirects_to_stripe_and_returns_without_trusting_query_parameters(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->post(route('payments.store', $order), ['payment_option' => 'stripe_card'])
            ->assertRedirect($payment->checkout_url);
        $this->get(route('payments.return', [$payment, 'status' => 'succeeded']))->assertOk();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'pending');
    }

    public function test_manual_verification_can_confirm_paid_session_and_is_idempotent(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->stripeSessions[$payment->provider_reference]['status'] = 'complete';
        $this->stripeSessions[$payment->provider_reference]['payment_status'] = 'paid';
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->get(route('payments.return', $payment))->assertRedirect(route('orders.show', $order));
        Http::assertSentCount(2);
    }

    public function test_creation_failure_retries_same_attempt_with_same_idempotency_key(): void
    {
        $order = $this->stripeOrder();
        $this->stripeUnavailable = true;
        $this->postJson(route('payments.store', $order), ['payment_option' => 'stripe_card'])->assertUnprocessable();
        $payment = $order->payments()->sole();
        $this->stripeUnavailable = false;
        $this->postJson(route('payments.retry', $payment))->assertCreated()->assertJsonPath('payment_id', $payment->getKey());
        $this->assertSame($this->stripeRequests[0]->header('Idempotency-Key'), $this->stripeRequests[1]->header('Idempotency-Key'));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_disabled_stripe_and_live_or_missing_keys_cannot_create_sessions(): void
    {
        $order = $this->stripeOrder();
        config(['payments.providers.stripe.enabled' => false]);
        $this->postJson(route('payments.store', $order), ['payment_option' => 'stripe_card'])
            ->assertUnprocessable()->assertJsonValidationErrors('payment_option');
        $this->assertDatabaseCount('payments', 0);
        config(['payments.providers.stripe.enabled' => true]);
        foreach ([null, 'sk_live_forbidden', 'rk_live_forbidden'] as $key) {
            config(['services.stripe.secret' => $key]);
            $this->postJson(route('payments.store', $order), ['payment_option' => 'stripe_card'])
                ->assertUnprocessable()->assertJsonValidationErrors('payment');
        }
        Http::assertNothingSent();
    }

    public function test_mismatched_remote_details_never_mark_order_paid(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $original = $this->stripeSessions[$payment->provider_reference];
        foreach ([['amount_total' => 1], ['currency' => 'eur'], ['client_reference_id' => '999'], ['livemode' => true], ['id' => 'cs_test_other']] as $change) {
            $this->stripeSessions[$payment->provider_reference] = array_replace($original, ['status' => 'complete', 'payment_status' => 'paid'], $change);
            $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
            $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        }
    }

    public function test_guest_and_another_customer_cannot_operate_on_payment(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('payments.store', $order), ['payment_option' => 'stripe_card'])->assertNotFound();
        $this->get(route('payments.return', $payment))->assertNotFound();
        $this->postJson(route('payments.complete', $payment))->assertNotFound();
        auth()->forgetGuards();
        $this->postJson(route('payments.store', $order), ['payment_option' => 'stripe_card'])->assertUnauthorized();
    }

    public function test_checkout_card_tab_uses_hosted_form_and_localized_sandbox_notice(): void
    {
        $order = $this->stripeOrder();
        $this->withSession(['locale' => 'vi'])->get(route('checkout.show', $order))->assertOk()
            ->assertSee('Tiếp tục với Stripe')->assertSee('value="stripe_card"', false)
            ->assertDontSee('demo-card-number')->assertDontSee('name="card_number"', false);
    }

    public function test_unsupported_currency_and_amount_are_rejected_before_api_calls(): void
    {
        foreach ([['USD', '0.49'], ['USD', '1000000.00'], ['EUR', '10.00'], ['VND', '25000.00']] as [$currency, $amount]) {
            try {
                app(StripePaymentGateway::class)->createPayment(new PaymentRequest(
                    '1', $amount, $currency, PaymentMethod::Card, 'test-request',
                    'https://example.test/return', 'https://example.test/cancel',
                ));
                $this->fail('Unsupported Stripe amount should be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('payment', $exception->errors());
            }
        }
        Http::assertNothingSent();
    }
}
