<?php

namespace App\View\Components\Cart;

use App\Queries\Address\AddressQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class OrderSummary extends Component
{
    public array $cartData;

    public Collection $addresses;

    public function __construct(AddressQuery $addressQuery, array $data)
    {
        $this->cartData = $data;
        $this->addresses = auth()->check() ? $addressQuery->forUser(auth()->user()) : collect();
    }

    public function render(): View
    {
        return view('components.cart.order-summary');
    }
}
