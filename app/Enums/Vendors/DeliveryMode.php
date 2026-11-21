<?php

namespace App\Enums\Vendors;

enum DeliveryMode: string
{
    case Domestic = 'domestic';
    case SelectedCountries = 'selected_countries';
    case Worldwide = 'worldwide';
}
