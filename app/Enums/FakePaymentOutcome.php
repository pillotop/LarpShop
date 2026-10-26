<?php

namespace App\Enums;

enum FakePaymentOutcome: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Delayed = 'delayed';
}
