<?php

namespace App\View\Components\Checkout;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OrderSummary extends Component
{
    public Order $order;

    public function __construct(Order $data)
    {
        $this->order = $data->loadMissing('details.product');
    }

    public function render(): View
    {
        return view('components.checkout.order-summary');
    }
}
