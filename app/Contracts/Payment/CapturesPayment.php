<?php

namespace App\Contracts\Payment;

use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentResult;

interface CapturesPayment
{
    public function capturePayment(string $reference, PaymentRequest $request): PaymentResult;
}
