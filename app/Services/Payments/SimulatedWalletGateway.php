<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentSession;

class SimulatedWalletGateway extends FakePaymentGateway
{
    public function createPayment(PaymentRequest $request): PaymentSession
    {
        $session = parent::createPayment($request);

        return new PaymentSession(
            reference: $session->reference,
            checkoutUrl: route('payments.wallet-demo.show', $request->reference),
            status: $session->status,
        );
    }
}
