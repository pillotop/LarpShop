<?php

namespace App\Contracts\Payment;

use App\Models\Order;

interface PaymentGateway
{
    public function createPayment(Order $order): PaymentSession;

    public function verifyPayment(string $reference): PaymentResult;
}
