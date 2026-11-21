<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentResult;
use App\Models\Payment;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookHandler
{
    private const EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
    ];

    public function __construct(private StripePaymentGateway $gateway, private PaymentService $payments) {}

    public function handle(string $payload, string $signature): void
    {
        $secret = config('services.stripe.webhook_secret');
        abort_unless(is_string($secret) && str_starts_with($secret, 'whsec_'), 503);
        try {
            $event = Webhook::constructEvent($payload, $signature, $secret)->toArray();
        } catch (SignatureVerificationException|UnexpectedValueException) {
            abort(400);
        }
        abort_unless(($event['livemode'] ?? null) === false, 400);
        if (! in_array($event['type'] ?? null, self::EVENTS, true)) {
            return;
        }
        $id = $event['id'] ?? null;
        $session = $event['data']['object'] ?? [];
        $paymentId = $session['metadata']['larpshop_payment_id'] ?? null;
        abort_unless(is_string($id) && str_starts_with($id, 'evt_') && strlen($id) <= 255, 400);

        // Ignore sessions created by another application on the same Stripe account.
        if (! is_string($paymentId) || ! ctype_digit($paymentId)) {
            return;
        }
        $payment = Payment::query()->where('provider', 'stripe')->find($paymentId);
        if ($payment === null) {
            return;
        }
        // Stripe can notify us before session creation has finished persisting locally.
        abort_if($payment->provider_reference === null, 503);
        abort_unless(($session['id'] ?? null) === $payment->provider_reference, 400);
        if ($payment->events()->where('provider_event_id', $id)->exists()) {
            return;
        }

        // Re-read Stripe so delayed or out-of-order notifications use the current state.
        $result = $this->gateway->verifyPayment($payment->provider_reference);
        if ($event['type'] === 'checkout.session.async_payment_failed' && $result->status === 'processing') {
            $result = new PaymentResult($result->reference, 'failed', $result->amount, $result->currency, $result->merchantReference);
        }
        $this->payments->recordProviderEvent($payment, $result, $id, $event['type']);
    }
}
