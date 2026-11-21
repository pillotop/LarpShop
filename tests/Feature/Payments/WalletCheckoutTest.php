<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWalletPayments;
use Tests\TestCase;

class WalletCheckoutTest extends TestCase
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
    public function test_checkout_freezes_an_authoritative_vnd_quote_and_reuses_the_attempt(string $provider): void
    {
        $order = $this->walletOrder();
        $response = $this->postJson(route('payments.store', $order), [
            'payment_option' => $provider, 'amount' => 1, 'currency' => 'USD', 'exchange_rate' => 1,
        ])->assertCreated();
        $payment = Payment::findOrFail($response->json('payment_id'));
        $this->assertSame('1248750.00', $payment->amount);
        $this->assertSame('VND', $payment->currency);
        $this->assertSame('49.95', $payment->source_amount);
        $this->assertSame('USD', $payment->source_currency);
        $this->assertSame('25000.00000000', $payment->exchange_rate);
        $this->assertSame('49.95', $order->fresh()->total_amount);
        Cache::put(config('currency.cache_key'), ['rate' => null]);
        $this->postJson(route('payments.store', $order), ['payment_option' => $provider])
            ->assertCreated()->assertJsonPath('payment_id', $payment->getKey());
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('1248750.00', $payment->fresh()->amount);
        $this->assertCount($provider === 'vnpay' ? 0 : 1, $this->walletCreates[$provider] ?? []);
    }

    #[DataProvider('providers')]
    public function test_disabled_provider_cannot_be_selected(string $provider): void
    {
        config(["payments.providers.{$provider}.enabled" => false]);
        $this->postJson(route('payments.store', $this->walletOrder()), ['payment_option' => $provider])
            ->assertUnprocessable()->assertJsonValidationErrors('payment_option');
        $this->assertDatabaseCount('payments', 0);
    }

    #[DataProvider('providers')]
    public function test_customer_can_check_a_payment_using_the_provider_api(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame('paid', $payment->order->fresh()->status->value);
        $this->assertCount(1, $this->walletQueries[$provider]);
    }

    #[DataProvider('providers')]
    public function test_another_customer_cannot_check_or_retry_the_payment(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $this->actingAs(User::factory()->create());
        $this->postJson(route('payments.complete', $payment))->assertNotFound();
        $this->postJson(route('payments.retry', $payment))->assertNotFound();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertEmpty($this->walletQueries);
    }

    public function test_an_unavailable_exchange_rate_does_not_create_a_payment(): void
    {
        Cache::put(config('currency.cache_key'), ['rate' => null]);
        $this->postJson(route('payments.store', $this->walletOrder()), ['payment_option' => 'momo'])
            ->assertUnprocessable()->assertJsonValidationErrors('payment');
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_exchange_conversion_rounds_to_whole_dong(): void
    {
        Cache::put(config('currency.cache_key'), ['rate' => '25000.5']);
        $this->assertSame('1248775.00', $this->walletPayment('momo')->amount);
    }

    public function test_out_of_range_amount_does_not_leave_an_active_attempt(): void
    {
        $order = $this->walletOrder();
        $order->update(['total_amount' => '0.01']);
        $this->postJson(route('payments.store', $order), ['payment_option' => 'momo'])
            ->assertUnprocessable()->assertJsonValidationErrors('payment');
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    #[DataProvider('providers')]
    public function test_browser_return_parameters_do_not_confirm_payment(string $provider): void
    {
        $payment = $this->walletPayment($provider);
        $this->get(route('payments.return', $payment).'?resultCode=0&vnp_ResponseCode=00&status=1')->assertOk();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status->value);
    }

    public function test_momo_and_zalopay_keep_the_same_reference_and_quote_after_an_http_failure(): void
    {
        foreach (['momo', 'zalopay'] as $provider) {
            $order = $this->walletOrder();
            $this->walletUnavailable = true;
            $this->postJson(route('payments.store', $order), ['payment_option' => $provider])->assertUnprocessable();
            $payment = $order->payments()->sole();
            $this->assertNotNull($payment->provider_reference);
            $this->walletUnavailable = false;
            Cache::put(config('currency.cache_key'), ['rate' => '26000']);
            $this->postJson(route('payments.retry', $payment))->assertCreated()->assertJsonPath('payment_id', $payment->getKey());
            $this->assertSame($this->walletCreates[$provider][0], $this->walletCreates[$provider][1]);
            $this->assertSame($payment->amount, $payment->fresh()->amount);
        }
    }
}
