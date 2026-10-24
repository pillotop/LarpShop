<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Cart extends Component
{
    public Collection $products;
    public int $totalCount;

    public function __construct(array $data)
    {
        $this->totalCount = $data['total_count'];
        $this->products = $data['products'];
    }

    public function render(): View
    {
        return view('components.cart');
    }
}
