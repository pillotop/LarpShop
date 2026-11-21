<?php

namespace Tests\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

trait CreatesStripePayments
{
    protected array $stripeSessions = [];

    protected array $stripeRequests = [];

    protected bool $stripeUnavailable = false;

    protected function fakeStripe(): void
    {
        Notification::fake();
        config([
            'payments.providers.stripe.enabled' => true,
            'payments.currency' => 'USD',
            'services.stripe.secret' => 'sk_test_fixture',
            'services.stripe.webhook_secret' => 'whsec_fixture',
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://api.stripe.com/v1/checkout/sessions*' => function (Request $request) {
            if ($request->method() === 'POST') {
                $this->stripeRequests[] = $request;
                if ($this->stripeUnavailable) {
                    return Http::response([], 503);
                }
                $reference = $request['client_reference_id'];
                $id = 'cs_test_payment_'.$reference;
                $this->stripeSessions[$id] ??= [
                    'id' => $id, 'object' => 'checkout.session', 'livemode' => false,
                    'mode' => 'payment', 'status' => 'open', 'payment_status' => 'unpaid',
                    'amount_total' => $request['line_items'][0]['price_data']['unit_amount'],
                    'currency' => $request['line_items'][0]['price_data']['currency'],
                    'client_reference_id' => $reference,
                    'metadata' => ['larpshop_payment_id' => $reference],
                    'url' => 'https://checkout.stripe.com/c/pay/'.$id,
                ];

                return Http::response($this->stripeSessions[$id]);
            }

            return Http::response($this->stripeSessions[basename($request->url())] ?? [], $this->stripeUnavailable ? 503 : 200);
        }]);
    }

    protected function stripeOrder(): Order
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = Order::create([
            'user_id' => $user->getKey(), 'total_amount' => '49.95',
            'status' => 'pending', 'expires_at' => now()->addMinutes(30),
        ]);
        $order->addresses()->create([
            'type' => 'shipping', 'recipient_name' => 'Test Buyer',
            'address_line_1' => '123 Test Street', 'city' => 'Test City', 'country_code' => 'VN',
        ]);

        return $order;
    }

    protected function stripePayment(Order $order): Payment
    {
        $id = $this->postJson(route('payments.store', $order), ['payment_option' => 'stripe_card'])
            ->assertCreated()->json('payment_id');

        return Payment::findOrFail($id);
    }

    protected function stripeEvent(Payment $payment, string $type = 'checkout.session.completed', string $id = 'evt_fixture', array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => $id, 'object' => 'event', 'type' => $type, 'livemode' => false,
            'data' => ['object' => $this->stripeSessions[$payment->provider_reference]],
        ], $overrides);
    }

    protected function sendStripeEvent(array $event, ?int $timestamp = null, string $secret = 'whsec_fixture'): TestResponse
    {
        $body = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        return $this->call('POST', route('payments.stripe.webhook'), server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], content: $body);
    }
}
