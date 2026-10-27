<?php

namespace App\Contracts\Payment;

use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentSession;
use App\Data\Payments\PaymentResult;

interface PaymentGateway
{
    public function createPayment(PaymentRequest $request): PaymentSession;

    public function verifyPayment(string $reference): PaymentResult;
}
