<?php

namespace App\Enums\Vendors;

enum OnboardingStep: string
{
    case Seller = 'seller';
    case Store = 'store';
    case Shipping = 'shipping';
    case Review = 'review';
}
