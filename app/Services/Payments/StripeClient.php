<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentRequest;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class StripeClient
{
    private function request(): PendingRequest
    {
        $secret = config('services.stripe.secret');
        if (! is_string($secret) || ! preg_match('/^(sk|rk)_test_\S+$/D', $secret)) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.stripe_configuration')]);
        }

        return Http::withToken($secret)->acceptJson()->asForm()->timeout(20)
            ->withHeaders(['Stripe-Version' => '2025-06-30.basil']);
    }

    public function createSession(PaymentRequest $payment, int $amount): array
    {
        return $this->session($this->request()->withHeaders(['Idempotency-Key' => $payment->requestId])
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'client_reference_id' => $payment->reference,
                'metadata' => ['larpshop_payment_id' => $payment->reference],
                'success_url' => $payment->returnUrl,
                'cancel_url' => $payment->cancelUrl,
                // Keep Stripe's localized price conversion separate from display preferences.
                'adaptive_pricing' => ['enabled' => 'false'],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($payment->currency),
                        'unit_amount' => $amount,
                        'product_data' => ['name' => 'LarpShop payment #'.$payment->reference],
                    ],
                ]],
            ]));
    }

    public function retrieveSession(string $reference): array
    {
        return $this->session($this->request()
            ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($reference)));
    }

    private function session(Response $response): array
    {
        $session = $response->throw()->json();
        if (! is_array($session)) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.provider_details')]);
        }

        return $session;
    }
}
