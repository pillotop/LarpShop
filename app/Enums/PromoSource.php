<?php

namespace App\Enums;

enum PromoSource: string
{
    case Collection = 'collection';
    case TopRated = 'top_rated';
    case NewArrivals = 'new_arrivals';

    public function label(): string
    {
        return __('promotions.sources.'.$this->value);
    }
}
