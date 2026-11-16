<?php

namespace App\View\Components\Cart;

use App\Models\Voucher;
use App\Queries\Address\AddressQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class OrderSummary extends Component
{
    public array $cartData;

    public Collection $addresses;

    public Collection $availableVouchers;

    public function __construct(AddressQuery $addressQuery, array $data)
    {
        $this->cartData = $data;
        $this->addresses = auth()->check() ? $addressQuery->forUser(auth()->user()) : collect();

        $vendorIds = collect($data['products'] ?? [])
            ->pluck('product.vendor_id')
            ->filter()
            ->unique()
            ->values();

        $this->availableVouchers = Voucher::query()
            ->available()
            ->with('vendor')
            ->where(function ($q) use ($vendorIds) {
                $q->whereNull('vendor_id');
                if ($vendorIds->isNotEmpty()) {
                    $q->orWhereIn('vendor_id', $vendorIds);
                }
            })
            ->orderByDesc('voucher_id')
            ->limit(6)
            ->get();
    }

    public function render(): View
    {
        return view('components.cart.order-summary');
    }
}
