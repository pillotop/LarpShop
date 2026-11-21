<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentResult;
use App\Models\Payment;

class SandboxNotificationHandler
{
    public function __construct(private PaymentService $payments) {}

    public function handle(string $provider, PaymentResult $result, string $signature): void
    {
        // Only adapters that authenticated the provider payload may call this method.
        $payment = Payment::query()->where('provider', $provider)->find($result->merchantReference);
        abort_if($payment === null, 404);
        abort_if($payment->provider_reference === null, 503);
        $this->payments->recordProviderEvent($payment, $result,
            hash('sha256', $signature), $provider.'.notification');
    }
}
