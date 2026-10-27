<?php

namespace App\Services\Payments;

use App\Contracts\Payment\CapturesPayment;
use App\Contracts\Payment\PaymentGateway;
use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentResult;
use App\Data\Payments\PaymentSession;
use App\Enums\PaymentStatus;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayPalPaymentGateway implements CapturesPayment, PaymentGateway
{
    public function __construct(
        private PayPalClient $client,
    ) {}

    public function createPayment(
        PaymentRequest $request,
    ): PaymentSession {
        $result = $this->client->createOrder(
            $request,
        );

        $approvalUrl = collect($result['links'] ?? [])
            ->first(fn (array $link) => in_array(
                $link['rel'] ?? null,
                ['payer-action', 'approve'],
                true,
            ))['href'] ?? null;

        if (
            ! is_string($result['id'] ?? null) ||
            ! is_string($approvalUrl)
        ) {
            throw new RuntimeException(
                'PayPal did not return a valid approval session.'
            );
        }

        return new PaymentSession(
            reference: $result['id'],
            checkoutUrl: $approvalUrl,
            status: PaymentStatus::Pending->value,
        );
    }

    public function verifyPayment(string $reference): PaymentResult
    {
        return $this->result($reference, $this->client->getOrder($reference));
    }

    public function capturePayment(string $reference, PaymentRequest $request): PaymentResult
    {
        $order = $this->client->getOrder($reference);
        $result = $this->result($reference, $order);
        if ($result->amount !== $request->amount || $result->currency !== $request->currency
            || $result->merchantReference !== $request->reference) {
            throw ValidationException::withMessages(['payment' => 'Provider payment details do not match this payment.']);
        }

        if (in_array($result->status, ['succeeded', 'processing', 'failed', 'cancelled'], true)) {
            return $result;
        }
        if (($order['status'] ?? null) !== 'APPROVED') {
            throw ValidationException::withMessages(['payment' => 'Approve the payment with PayPal before completing it.']);
        }

        return $this->result($reference, $this->client->captureOrder($reference, $request->requestId.'-capture'));
    }

    private function result(string $reference, array $order): PaymentResult
    {
        $units = $order['purchase_units'] ?? [];
        if (($order['id'] ?? null) !== $reference || count($units) !== 1) {
            throw new RuntimeException('Invalid PayPal order response.');
        }
        $unit = $units[0];
        $captures = $unit['payments']['captures'] ?? [];
        if (count($captures) > 1) {
            throw new RuntimeException('Unexpected partial PayPal captures.');
        }
        $capture = $captures[0] ?? null;
        $amount = $capture['amount'] ?? $unit['amount'] ?? [];
        if (! is_string($amount['value'] ?? null) || ! is_string($amount['currency_code'] ?? null)
            || ! is_string($unit['reference_id'] ?? null)) {
            throw new RuntimeException('PayPal omitted payment reconciliation details.');
        }

        $status = $capture !== null
            ? match ($capture['status'] ?? null) {
                'COMPLETED' => 'succeeded',
                'PENDING' => 'processing',
                'DECLINED', 'FAILED' => 'failed',
                default => throw new RuntimeException('Unsupported PayPal capture state.'),
            }
        : match ($order['status'] ?? null) {
            'CREATED', 'SAVED', 'APPROVED', 'PAYER_ACTION_REQUIRED' => 'pending',
            'VOIDED' => 'cancelled',
            default => throw new RuntimeException('PayPal order has no verifiable capture.'),
        };

        return new PaymentResult($reference, $status, $amount['value'], $amount['currency_code'], $unit['reference_id']);
    }
}
