<?php

namespace Tests\Feature\Payments;

use App\Data\Payments\PaymentRequest;
use App\Enums\FakePaymentOutcome;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_request_replay_preserves_the_session_and_its_outcome(): void
    {
        $request = new PaymentRequest(
            reference: '1',
            amount: '49.95',
            currency: 'USD',
            method: PaymentMethod::Card,
            requestId: 'payment-replay-test',
            returnUrl: 'https://example.test/return',
            cancelUrl: 'https://example.test/cancel',
        );
        $gateway = app(FakePaymentGateway::class);
        $session = $gateway->createPayment($request);
        $gateway->simulate($session->reference, FakePaymentOutcome::Succeeded);
        $replayed = $gateway->createPayment($request);

        $this->assertSame($session->reference, $replayed->reference);
        $this->assertSame('succeeded', $replayed->status);
        $this->assertDatabaseCount('fake_payment_sessions', 1);
    }

    public function test_paypal_uses_the_payment_snapshot_and_persists_its_checkout_url(): void
    {
        Http::preventStrayRequests();
        config([
            'services.paypal.base_url' => 'https://paypal.example.test',
            'services.paypal.client_id' => 'test-client',
            'services.paypal.client_secret' => 'test-secret',
            'payments.currency' => 'EUR',
        ]);
        Http::fake([
            'https://paypal.example.test/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'https://paypal.example.test/v2/checkout/orders' => Http::response([
                'id' => 'provider-order-1',
                'links' => [['rel' => 'approve', 'href' => 'https://paypal.example.test/approve/1']],
            ]),
        ]);
        $order = Order::create([
            'user_id' => User::factory()->create()->getKey(),
            'total_amount' => '49.95',
            'status' => 'pending',
        ]);
        $service = app(PaymentService::class);
        $payment = $service->createForOrder($order, 'paypal', PaymentMethod::EWallet);

        $this->assertSame('https://paypal.example.test/approve/1', $payment->fresh()->checkout_url);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://paypal.example.test/v2/checkout/orders'
            && $request->hasHeader('PayPal-Request-Id', 'larpshop-payment-'.$payment->getKey())
            && $request['purchase_units'][0]['reference_id'] === (string) $payment->getKey()
            && $request['purchase_units'][0]['amount'] === ['currency_code' => 'EUR', 'value' => '49.95']
            && $request['payment_source']['paypal']['experience_context']['return_url'] === route('payments.return', $payment));

        $replayed = $service->createForOrder($order, 'paypal', PaymentMethod::EWallet);
        $this->assertSame($payment->getKey(), $replayed->getKey());
        Http::assertSentCount(2);
        $this->assertDatabaseCount('payment_events', 1);
    }
}
