<?php

namespace App\Services\Payments;

use App\Contracts\Payment\PaymentGateway;
use App\Data\Payments\PaymentResult;
use App\Data\Payments\PaymentSession;
use App\Enums\PaymentStatus;
use App\Enums\FakePaymentOutcome;
use App\Models\FakePaymentSession;
use App\Data\Payments\PaymentRequest;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FakePaymentGateway implements PaymentGateway
{
    public function createPayment(PaymentRequest $request): PaymentSession
    {
        $session = FakePaymentSession::firstOrCreate([
            'reference' => Uuid::uuid5(Uuid::NAMESPACE_URL, $request->requestId)->toString(),
        ], [
            'status' => 'pending',
        ]);

        return new PaymentSession(
            reference: $session->reference,
            checkoutUrl: null,
            status: $session->status,
        );
    }

    public function verifyPayment(string $reference): PaymentResult
    {
        return DB::transaction(function () use ($reference) {
            $session = FakePaymentSession::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $session->status === PaymentStatus::Processing->value &&
                $session->ready_at?->isPast()
            ) {
                $session->update([
                    'status' => $session->final_outcome,
                    'final_outcome' => null,
                    'ready_at' => null,
                ]);
            }

            return new PaymentResult(
                reference: $session->reference,
                status: $session->status,
            );
        });
    }

    public function simulate(string $reference, FakePaymentOutcome $outcome): void
    {
        DB::transaction(function () use ($reference, $outcome) {
            $session = FakePaymentSession::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => 'This payment has already been processed.',
                ]);
            }

            match ($outcome) {
                FakePaymentOutcome::Succeeded => $session->update([
                    'status' => PaymentStatus::Succeeded->value,
                ]),

                FakePaymentOutcome::Failed => $session->update([
                    'status' => PaymentStatus::Failed->value,
                ]),

                FakePaymentOutcome::Cancelled => $session->update([
                    'status' => PaymentStatus::Cancelled->value,
                ]),

                FakePaymentOutcome::Delayed => $session->update([
                    'status' => PaymentStatus::Processing->value,
                    'final_outcome' => PaymentStatus::Succeeded->value,
                    'ready_at' => now()->addSeconds(30),
                ]),
            };
        });
    }
}
