<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Notifications\PaymentSucceededNotification;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWalletPayments;
use Tests\TestCase;

class WalletWebhookTest extends TestCase
{
    use CreatesWalletPayments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeWallets();
    }

    public static function providers(): array
    {
        return [['momo'], ['zalopay'], ['vnpay']];
    }

    #[DataProvider('providers')]
    public function test_signed_callbacks_complete_the_order_once_without_a_customer_session(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $body = $this->walletNotification($payment);
        $first = $this->sendWalletNotification($provider, $body)->assertSuccessful();
        if ($provider === 'zalopay') {
            $first->assertJsonPath('return_code', 1);
        } elseif ($provider === 'vnpay') {
            $first->assertJsonPath('RspCode', '00');
        } else {
            $first->assertNoContent();
        }
        $this->sendWalletNotification($provider, $body)->assertSuccessful();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame('paid', $payment->order->fresh()->status->value);
        $this->assertSame(1, $payment->events()->whereNotNull('provider_event_id')->count());
        $this->assertSame(1, $payment->events()->where('event_type', 'payment_status_changed')->count());
        Notification::assertSentToTimes($payment->order->user, PaymentSucceededNotification::class, 1);
    }

    #[DataProvider('providers')]
    public function test_modified_signatures_and_nested_payloads_do_not_change_payments(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $body = $this->walletNotification($payment);
        $field = match ($provider) {
            'momo' => 'signature', 'zalopay' => 'mac', 'vnpay' => 'vnp_SecureHash'
        };
        $body[$field] = 'invalid';
        $this->sendWalletNotification($provider, $body);
        $body[$field] = ['nested'];
        $this->sendWalletNotification($provider, $body);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, $payment->events()->whereNotNull('provider_event_id')->count());
        Notification::assertNothingSent();
    }

    #[DataProvider('providers')]
    public function test_even_signed_amount_tampering_is_rejected(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $field = $provider === 'vnpay' ? 'vnp_Amount' : 'amount';
        $this->sendWalletNotification($provider, $this->walletNotification($payment, [$field => 100]));
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status->value);
        $this->assertSame(0, $payment->events()->whereNotNull('provider_event_id')->count());
    }

    #[DataProvider('providers')]
    public function test_another_merchant_cannot_confirm_this_payment(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $field = match ($provider) {
            'momo' => 'partnerCode', 'zalopay' => 'app_id', 'vnpay' => 'vnp_TmnCode'
        };
        $this->sendWalletNotification($provider, $this->walletNotification($payment, [$field => 'OTHER_MERCHANT']));
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    #[DataProvider('providers')]
    public function test_late_success_does_not_reopen_a_cancelled_order(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $payment->order->update(['status' => 'cancelled', 'stock_released_at' => now()]);
        $this->sendWalletNotification($provider, $this->walletNotification($payment))->assertSuccessful();
        $this->assertSame(PaymentStatus::RefundRequired, $payment->fresh()->status);
        $this->assertSame('cancelled', $payment->order->fresh()->status->value);
        Notification::assertNothingSent();
    }

    public function test_momo_failure_can_be_retried_with_a_new_payment_attempt(): void
    {
        $payment = $this->walletPayment('momo');
        $this->sendWalletNotification('momo', $this->walletNotification($payment, ['resultCode' => 1006]))->assertNoContent();
        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $this->actingAs($payment->order->user);
        $next = $this->walletPayment('momo', $payment->order);
        $this->assertNotSame($payment->getKey(), $next->getKey());
        $this->assertNotSame($payment->provider_reference, $next->provider_reference);
    }

    public function test_momo_processing_can_eventually_succeed(): void
    {
        $payment = $this->walletPayment('momo');
        $this->sendWalletNotification('momo', $this->walletNotification($payment, ['resultCode' => 7000]))->assertNoContent();
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->sendWalletNotification('momo', $this->walletNotification($payment, ['resultCode' => 1000]))->assertNoContent();
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->sendWalletNotification('momo', $this->walletNotification($payment))->assertNoContent();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }

    public function test_zalopay_does_not_accept_a_callback_signed_with_the_create_key(): void
    {
        $payment = $this->walletPayment('zalopay');
        $body = $this->walletNotification($payment);
        $body['mac'] = hash_hmac('sha256', $body['data'], 'zalo-create-fixture');
        $this->sendWalletNotification('zalopay', $body)->assertOk()->assertJsonPath('return_code', 2);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_vnpay_requires_successful_transaction_status_and_response_code(): void
    {
        $payment = $this->walletPayment('vnpay');
        $body = $this->walletNotification($payment, ['vnp_ResponseCode' => '24']);
        $this->sendWalletNotification('vnpay', $body)->assertOk()->assertJsonPath('RspCode', '97');
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_callback_can_arrive_after_an_initialization_timeout(): void
    {
        $this->walletUnavailable = true;
        $order = $this->walletOrder();
        $this->postJson(route('payments.store', $order), ['payment_option' => 'momo'])->assertUnprocessable();
        $payment = $order->payments()->sole();
        $this->assertNull($payment->checkout_url);
        $this->sendWalletNotification('momo', $this->walletNotification($payment))->assertNoContent();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }

    #[DataProvider('providers')]
    public function test_only_signed_callback_endpoints_bypass_csrf(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->postJson(route('payments.complete', $payment))->assertStatus(419);
        $this->sendWalletNotification($provider, $this->walletNotification($payment))->assertSuccessful();
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }
}
