<?php

namespace App\Services\Payments;

use App\Contracts\Payment\PaymentGateway;
use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentResult;
use App\Data\Payments\PaymentSession;
use App\Enums\PaymentMethod;
use Illuminate\Validation\ValidationException;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(private StripeClient $client) {}

    public function createPayment(PaymentRequest $request): PaymentSession
    {
        if ($request->currency !== 'USD' || $request->method !== PaymentMethod::Card
            || ! preg_match('/^\d{1,6}\.\d{2}$/D', $request->amount)) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.stripe_amount')]);
        }
        [$whole, $fraction] = explode('.', $request->amount);
        $amount = (int) $whole * 100 + (int) $fraction;
        if ($amount < 50 || $amount > 99999999) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.stripe_amount')]);
        }
        $session = $this->client->createSession($request, $amount);
        $result = $this->result($session);
        $url = $session['url'] ?? null;
        if ($result->amount !== $request->amount || $result->currency !== $request->currency
            || $result->merchantReference !== $request->reference || ! is_string($url)
            || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || parse_url($url, PHP_URL_HOST) !== 'checkout.stripe.com') {
            $this->invalidDetails();
        }

        return new PaymentSession($result->reference, $url, 'pending');
    }

    public function verifyPayment(string $reference): PaymentResult
    {
        if (! str_starts_with($reference, 'cs_test_')) {
            $this->invalidDetails();
        }
        $result = $this->result($this->client->retrieveSession($reference));
        if ($result->reference !== $reference) {
            $this->invalidDetails();
        }

        return $result;
    }

    private function result(array $session): PaymentResult
    {
        $reference = $session['id'] ?? null;
        $amount = $session['amount_total'] ?? null;
        $merchantReference = $session['client_reference_id'] ?? null;
        if (($session['livemode'] ?? null) !== false || ($session['mode'] ?? null) !== 'payment'
            || ! is_string($reference) || ! str_starts_with($reference, 'cs_test_')
            || ! is_int($amount) || $amount < 50 || $amount > 99999999
            || ($session['currency'] ?? null) !== 'usd'
            || ! is_string($merchantReference) || ! ctype_digit($merchantReference)
            || ($session['metadata']['larpshop_payment_id'] ?? null) !== $merchantReference) {
            $this->invalidDetails();
        }

        $status = match (true) {
            ($session['status'] ?? null) === 'complete' && ($session['payment_status'] ?? null) === 'paid' => 'succeeded',
            ($session['status'] ?? null) === 'complete' && ($session['payment_status'] ?? null) === 'unpaid' => 'processing',
            ($session['status'] ?? null) === 'expired' && ($session['payment_status'] ?? null) === 'unpaid' => 'cancelled',
            ($session['status'] ?? null) === 'open' && ($session['payment_status'] ?? null) === 'unpaid' => 'pending',
            default => $this->invalidDetails(),
        };

        return new PaymentResult(
            reference: $reference,
            status: $status,
            amount: intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT),
            currency: 'USD',
            merchantReference: $merchantReference,
        );
    }

    private function invalidDetails(): never
    {
        throw ValidationException::withMessages(['payment' => __('checkout.errors.provider_details')]);
    }
}
