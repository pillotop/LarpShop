<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PayPalClient
{
    public function accessToken(): string
    {
        if (! config('services.paypal.client_id') || ! config('services.paypal.client_secret')) {
            throw ValidationException::withMessages([
                'payment' => 'PayPal is not configured yet. Please choose another payment method.',
            ]);
        }
        $response = Http::withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.client_secret'),
        )
            ->asForm()
            ->timeout(15)
            ->post(
                config('services.paypal.base_url')
                    .'/v1/oauth2/token',
                [
                    'grant_type' => 'client_credentials',
                ]
            )
            ->throw();

        return $response->json('access_token');
    }

    public function createOrder(
        PaymentRequest $request,
    ): array {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(20)
            ->withHeaders([
                'PayPal-Request-Id' => $request->requestId,
                'Prefer' => 'return=representation',
            ])
            ->post(
                rtrim(config('services.paypal.base_url'), '/')
                    .'/v2/checkout/orders',
                [
                    'intent' => 'CAPTURE',

                    'purchase_units' => [
                        [
                            'reference_id' => $request->reference,
                            'amount' => [
                                'currency_code' => $request->currency,
                                'value' => $request->amount,
                            ],
                        ],
                    ],

                    'payment_source' => [
                        'paypal' => [
                            'experience_context' => [
                                'return_url' => $request->returnUrl,
                                'cancel_url' => $request->cancelUrl,
                                'user_action' => 'PAY_NOW',
                            ],
                        ],
                    ],
                ]
            )
            ->throw();

        return $response->json();
    }

    public function getOrder(string $reference): array
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()->timeout(20)
            ->get(rtrim(config('services.paypal.base_url'), '/').'/v2/checkout/orders/'.rawurlencode($reference))
            ->throw()->json();
    }

    public function captureOrder(string $reference, string $requestId): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()->timeout(20)
            ->withHeaders(['PayPal-Request-Id' => $requestId, 'Prefer' => 'return=representation'])
            ->withBody('{}', 'application/json')
            ->post(rtrim(config('services.paypal.base_url'), '/').'/v2/checkout/orders/'.rawurlencode($reference).'/capture');

        if ($response->status() === 422 && collect($response->json('details', []))->contains('issue', 'ORDER_ALREADY_CAPTURED')) {
            return $this->getOrder($reference);
        }

        return $response->throw()->json();
    }
}
