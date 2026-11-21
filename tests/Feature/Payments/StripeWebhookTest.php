<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Notifications\PaymentSucceededNotification;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreatesStripePayments;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use CreatesStripePayments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeStripe();
    }

    public function test_signed_webhook_completes_order_without_browser_return_and_only_once(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->stripeSessions[$payment->provider_reference]['status'] = 'complete';
        $this->stripeSessions[$payment->provider_reference]['payment_status'] = 'paid';
        $event = $this->stripeEvent($payment);
        auth()->forgetGuards();
        $this->sendStripeEvent($event)->assertNoContent();
        $this->sendStripeEvent($event)->assertNoContent();
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(1, $payment->events()->where('provider_event_id', 'evt_fixture')->count());
        $this->assertSame(1, $payment->events()->where('event_type', 'payment_status_changed')->count());
        Http::assertSentCount(2);
        Notification::assertSentToTimes($order->user, PaymentSucceededNotification::class, 1);
    }

    public function test_invalid_signature_missing_signature_expired_timestamp_and_live_event_are_rejected(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $event = $this->stripeEvent($payment);
        $this->postJson(route('payments.stripe.webhook'), $event)->assertBadRequest();
        $this->sendStripeEvent($event, secret: 'whsec_wrong')->assertBadRequest();
        $this->sendStripeEvent($event, timestamp: time() - 600)->assertBadRequest();
        $this->sendStripeEvent(array_replace($event, ['livemode' => true]))->assertBadRequest();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_forged_success_cannot_override_current_stripe_state(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $event = $this->stripeEvent($payment, overrides: ['data' => ['object' => ['status' => 'complete', 'payment_status' => 'paid']]]);
        $this->sendStripeEvent($event)->assertNoContent();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_processing_can_eventually_succeed_and_delayed_expiry_cannot_regress_it(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->stripeSessions[$payment->provider_reference]['status'] = 'complete';
        $this->sendStripeEvent($this->stripeEvent($payment, id: 'evt_processing'))->assertNoContent();
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->stripeSessions[$payment->provider_reference]['payment_status'] = 'paid';
        $this->sendStripeEvent($this->stripeEvent($payment, 'checkout.session.async_payment_succeeded', 'evt_paid'))->assertNoContent();
        $this->sendStripeEvent($this->stripeEvent($payment, 'checkout.session.expired', 'evt_late_expired'))->assertNoContent();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        Notification::assertSentToTimes($order->user, PaymentSucceededNotification::class, 1);
    }

    public function test_expired_session_can_be_replaced_by_a_new_attempt(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->stripeSessions[$payment->provider_reference]['status'] = 'expired';
        $this->sendStripeEvent($this->stripeEvent($payment, 'checkout.session.expired'))->assertNoContent();
        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $retry = $this->stripePayment($order);
        $this->assertNotSame($payment->getKey(), $retry->getKey());
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_async_failure_can_be_retried_without_corrupting_the_order(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->stripeSessions[$payment->provider_reference]['status'] = 'complete';
        $this->sendStripeEvent($this->stripeEvent($payment, 'checkout.session.async_payment_failed'))->assertNoContent();
        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->stripePayment($order);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_payment_received_after_order_expiration_requires_refund_review(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $order->update(['expires_at' => now()->subSecond()]);
        $this->stripeSessions[$payment->provider_reference]['status'] = 'complete';
        $this->stripeSessions[$payment->provider_reference]['payment_status'] = 'paid';
        $this->sendStripeEvent($this->stripeEvent($payment))->assertNoContent();
        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
        $this->assertSame(PaymentStatus::RefundRequired, $payment->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_remote_mismatch_or_api_failure_does_not_acknowledge_event_as_processed(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $event = $this->stripeEvent($payment);
        $this->stripeSessions[$payment->provider_reference]['amount_total'] = 5000;
        $this->sendStripeEvent($event)->assertUnprocessable();
        $this->assertSame(0, $payment->events()->whereNotNull('provider_event_id')->count());
        $this->stripeSessions[$payment->provider_reference]['amount_total'] = 4995;
        $this->stripeUnavailable = true;
        $this->sendStripeEvent($event)->assertStatus(503);
        $this->stripeUnavailable = false;
        $this->sendStripeEvent($event)->assertNoContent();
        $this->assertSame(1, $payment->events()->whereNotNull('provider_event_id')->count());
    }

    public function test_unpersisted_reference_retries_and_mismatched_session_is_rejected(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $event = $this->stripeEvent($payment);
        $reference = $payment->provider_reference;
        $payment->update(['provider_reference' => null]);
        $this->sendStripeEvent($event)->assertStatus(503);
        $payment->update(['provider_reference' => $reference]);
        $event['data']['object']['id'] = 'cs_test_different';
        $this->sendStripeEvent($event)->assertBadRequest();
    }

    public function test_only_the_webhook_bypasses_csrf_and_other_application_events_are_ignored(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->sendStripeEvent($this->stripeEvent($payment, 'customer.created'))->assertNoContent();
        $event = $this->stripeEvent($payment);
        unset($event['data']['object']['metadata']['larpshop_payment_id']);
        $this->sendStripeEvent($event)->assertNoContent();
        $this->postJson(route('payments.complete', $payment))->assertStatus(419);
        Http::assertSentCount(1);
    }

    public function test_missing_webhook_secret_returns_retryable_error_without_processing(): void
    {
        $order = $this->stripeOrder();
        $payment = $this->stripePayment($order);
        config(['services.stripe.webhook_secret' => null]);
        $this->sendStripeEvent($this->stripeEvent($payment))->assertStatus(503);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        Http::assertSentCount(1);
    }
}
