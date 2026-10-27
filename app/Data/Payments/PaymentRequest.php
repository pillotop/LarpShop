<?php

namespace App\Data\Payments;

use App\Enums\PaymentMethod;

final readonly class PaymentRequest
{
    public function __construct(
        public string $reference,
        public string $amount,
        public string $currency,
        public PaymentMethod $method,
        public string $requestId,
        public string $returnUrl,
        public string $cancelUrl,
    ) {}
}
