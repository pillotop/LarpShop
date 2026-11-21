<?php

namespace App\Contracts\Payment;

use App\Data\Payments\PaymentRequest;

interface UsesMerchantReference
{
    /** Return a stable local reference that can be persisted before contacting the provider. */
    public function referenceFor(PaymentRequest $request): string;
}
