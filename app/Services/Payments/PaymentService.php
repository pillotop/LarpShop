<?php

namespace App\Services\Payments;

use App\Contracts\Payment\CapturesPayment;
use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private PaymentGatewayFactory $gateways,
        private OrderLifecycleService $lifecycle,
    ) {}

    public function createForOrder(
        Order $order,
        string $provider,
        PaymentMethod $method,
    ): Payment {
        if (! $this->gateways->supports($provider, $method)) {
            throw ValidationException::withMessages([
                'method' => 'Unsupported payment method for this provider',
            ]);
        }

        $lockKey = "payment-initiation:order:{$order->getKey()}";

        return Cache::lock($lockKey, 90)->block(
            10,
            function () use ($order, $provider, $method) {
                $payment = DB::transaction(function () use (
                    $order,
                    $provider,
                    $method,
                ) {
                    $order = Order::query()
                        ->whereKey($order->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $order->status !== OrderStatus::Pending ||
                        (
                            $order->expires_at !== null &&
                            $order->expires_at->lessThanOrEqualTo(now())
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'order' => 'This order cannot be paid',
                        ]);
                    }

                    $existing = $order->payments()
                        ->whereIn('status', [
                            PaymentStatus::Pending->value,
                            PaymentStatus::Processing->value,
                            PaymentStatus::Succeeded->value,
                        ])
                        ->first();

                    if ($existing !== null) {
                        if (
                            $existing->status !== PaymentStatus::Pending ||
                            $existing->provider !== $provider ||
                            $existing->method !== $method
                        ) {
                            throw ValidationException::withMessages([
                                'payment' => 'This order already has an active payment.',
                            ]);
                        }

                        return $existing;
                    }

                    return $order->payments()->create([
                        'provider' => $provider,
                        'method' => $method,
                        'amount' => $order->total_amount,
                        'currency' => config('payments.currency', 'USD'),
                        'status' => PaymentStatus::Pending,
                    ]);
                }, attempts: 3);

                // Already initialized with the provider
                if ($payment->provider_reference !== null
                    && ($payment->checkout_url !== null || ! config("payments.providers.{$payment->provider}.redirect", false))) {
                    return $payment;
                }

                // Phase 2: initiate the provider transaction
                // no db transaction is held here
                $request = $this->requestFor($payment);

                $session = $this->gateways
                    ->make($payment->provider)
                    ->createPayment($request);

                // Phase 3: Persist the provider response
                return DB::transaction(function () use (
                    $payment,
                    $session,
                ) {
                    $payment = Payment::query()
                        ->whereKey($payment->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $payment->update([
                        'provider_reference' => $session->reference,
                        'checkout_url' => $session->checkoutUrl,
                    ]);

                    $payment->events()->firstOrCreate([
                        'event_type' => 'payment_created',
                        'status' => PaymentStatus::Pending->value,
                    ]);

                    return $payment->refresh();
                }, attempts: 3);
            }
        );
    }

    public function verify(Payment $payment): PaymentResult
    {
        if ($payment->provider_reference === null) {
            throw ValidationException::withMessages([
                'payment' => 'Payment has no provider reference.',
            ]);
        }

        $gateway = $this->gateways->make($payment->provider);

        $result = $gateway->verifyPayment(
            $payment->provider_reference
        );

        return $this->applyResult($payment, $result);
    }

    public function complete(Payment $payment): PaymentResult
    {
        return Cache::lock("payment-completion:{$payment->getKey()}", 180)->block(10, function () use ($payment) {
            $payment = $payment->fresh();
            if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
                return new PaymentResult($payment->provider_reference ?? '', $payment->status->value);
            }
            if ($payment->provider_reference === null) {
                throw ValidationException::withMessages(['payment' => 'Initialize the payment before completing it.']);
            }
            $gateway = $this->gateways->make($payment->provider);
            $result = $gateway->verifyPayment($payment->provider_reference);
            if ($result->status === PaymentStatus::Pending->value && $gateway instanceof CapturesPayment) {
                $order = $payment->order;
                if ($order->status !== OrderStatus::Pending || $order->expires_at?->lessThanOrEqualTo(now())) {
                    throw ValidationException::withMessages(['payment' => 'This order can no longer accept a new capture.']);
                }
                $result = $gateway->capturePayment($payment->provider_reference, $this->requestFor($payment));
            }

            return $this->applyResult($payment, $result);
        });
    }

    private function requestFor(Payment $payment): PaymentRequest
    {
        return new PaymentRequest(
            reference: (string) $payment->getKey(),
            amount: $payment->amount,
            currency: $payment->currency,
            method: $payment->method,
            requestId: "larpshop-payment-{$payment->getKey()}",
            returnUrl: route('payments.return', $payment),
            cancelUrl: route('payments.cancel', $payment),
        );
    }

    private function applyResult(Payment $payment, PaymentResult $result): PaymentResult
    {
        if (($result->amount !== null && $result->amount !== $payment->amount)
            || ($result->currency !== null && $result->currency !== $payment->currency)
            || ($result->merchantReference !== null && $result->merchantReference !== (string) $payment->getKey())) {
            throw ValidationException::withMessages(['payment' => 'Provider payment details do not match this payment.']);
        }

        return DB::transaction(function () use ($payment, $result) {
            // Serialize payment completion with cancellation and expiration.
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->provider_reference !== $result->reference) {
                throw ValidationException::withMessages([
                    'payment' => 'Provider reference does not match',
                ]);
            }

            $newStatus = PaymentStatus::from($result->status);
            $oldStatus = $payment->status;

            if ($oldStatus === PaymentStatus::RefundRequired && $newStatus === PaymentStatus::Succeeded) {
                return new PaymentResult(reference: $result->reference, status: $oldStatus->value);
            }

            // A delayed provider read must not regress an in-flight capture.
            if ($oldStatus === PaymentStatus::Processing && $newStatus === PaymentStatus::Pending) {
                return new PaymentResult($result->reference, $oldStatus->value);
            }

            if ($oldStatus === $newStatus) {
                return $result;
            }

            // Terminal states must not transition again in this
            // initial payment-attempt lifecycle.
            if (
                in_array($oldStatus, [
                    PaymentStatus::Succeeded,
                    PaymentStatus::Failed,
                    PaymentStatus::Cancelled,
                    PaymentStatus::Refunded,
                    PaymentStatus::RefundRequired,
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'This payment is already finalized.',
                ]);
            }

            $lateSuccess = false;
            if ($newStatus === PaymentStatus::Succeeded) {
                if ($order->status === OrderStatus::Pending && $order->expires_at?->lessThanOrEqualTo(now())) {
                    $order = $this->lifecycle->expire($order);
                }
                $lateSuccess = $order->status !== OrderStatus::Pending || $order->stock_released_at !== null;
                if ($lateSuccess) {
                    $newStatus = PaymentStatus::RefundRequired;
                }
            }

            $payment->update([
                'status' => $newStatus,
                'completed_at' => in_array($newStatus, [
                    PaymentStatus::Succeeded,
                    PaymentStatus::Failed,
                    PaymentStatus::Cancelled,
                    PaymentStatus::RefundRequired,
                ], true) ? now() : null,
            ]);

            $payment->events()->create([
                'event_type' => 'payment_status_changed',
                'status' => $newStatus->value,
                'metadata' => [
                    'previous_status' => $oldStatus->value,
                    ...($lateSuccess ? ['provider_status' => $result->status, 'reason' => 'order_no_longer_payable'] : []),
                ],
            ]);

            if ($newStatus === PaymentStatus::Succeeded) {
                $order->update(['status' => OrderStatus::Paid]);

                DB::afterCommit(function () use ($order, $payment) {
                    $order->user->notify(
                        new PaymentSucceededNotification($payment)
                    );
                });
            }

            return new PaymentResult(reference: $result->reference, status: $newStatus->value);
        }, attempts: 3);
    }
}
