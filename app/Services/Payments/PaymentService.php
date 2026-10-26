<?php

namespace App\Services\Payments;

use App\Contracts\Payment\PaymentGateway;
use App\Data\Payments\PaymentResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Orders\OrderLifecycleService;
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
        return DB::transaction(function () use ($order, $provider, $method) {
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $order->status !== OrderStatus::Pending ||
                ($order->expires_at !== null && $order->expires_at->lessThanOrEqualTo(now()))
            ) {
                throw ValidationException::withMessages([
                    'order' => 'This order cannot be paid.',
                ]);
            }

            $hasActivePayment = $order->payments()
                ->whereIn('status', [
                    PaymentStatus::Pending->value,
                    PaymentStatus::Processing->value,
                    PaymentStatus::Succeeded->value,
                ])
                ->exists();

            if ($hasActivePayment) {
                throw ValidationException::withMessages([
                    'payment' => 'This order already has an active payment',
                ]);
            }

            if (! $this->gateways->supports($provider, $method)) {
                throw ValidationException::withMessages([
                    'method' => 'Unsupported payment method for this provider.',
                ]);
            }

            $gateway = $this->gateways->make($provider);
            $session = $gateway->createPayment($order);
            $payment = $order->payments()->create([
                'provider' =>  $provider,
                'method' => $method,
                'amount' => $order->total_amount,
                'currency' => config('payments.currency', 'USD'),
                'status' => PaymentStatus::Pending,
            ]);

            $payment->update([
                'provider_reference' => $session->reference,
            ]);

            $payment->events()->create([
                'event_type' => 'payment_created',
                'status' => PaymentStatus::Pending->value,
            ]);

            return $payment->refresh();
        });
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
