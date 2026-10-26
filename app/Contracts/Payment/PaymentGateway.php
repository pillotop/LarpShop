<?php

namespace App\Contracts\Payment;

use App\Models\Order;
use App\Data\Payments\PaymentSession;
use App\Data\Payments\PaymentResult;

interface PaymentGateway
{
    public function createPayment(Order $order): PaymentSession;

    public function verifyPayment(string $reference): PaymentResult;
}
