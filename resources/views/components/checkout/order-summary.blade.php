<section
    {{ $attributes->class(['card border-0 shadow-sm rounded-9 p-4']) }}
    aria-label="{{ __('checkout.summary') }}"
>
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">
            {{ __('checkout.summary') }}
        </h2>
        <span class="badge theme-secondary theme-contrast">
            {{ __('checkout.order_status.' . $order->status->value) }}
        </span>
    </div>
    <p class="small fg-3">{{ trans_choice('checkout.order_items', $order->details->sum('quantity'), ['order' => $order->getKey(), 'count' => $order->details->sum('quantity')]) }}</p>
    <ul class="list-unstyled mb-0">
        @forelse ($order->details as $detail)
            <li class="border-bottom py-3">
                <div class="d-flex justify-content-between gap-3">
                    <span class="fw-semibold">{{ $detail->product?->name ?? __('checkout.unavailable_product') }}</span>
                    <span class="text-nowrap">{{ __('cart.each', ['price' => '$' . number_format((float) $detail->unit_price, 2)]) }}</span>
                </div>
                <span class="small fg-3">{{ __('checkout.quantity', ['count' => $detail->quantity]) }}</span>
            </li>
        @empty
            <li class="fg-3 py-3">{{ __('checkout.no_items') }}</li>
        @endforelse
    </ul>
    <dl class="d-flex justify-content-between align-items-baseline gap-3 mt-4 mb-0">
        <dt>{{ __('checkout.total') }}</dt>
        <dd class="h4 fw-bold mb-0">${{ number_format((float) $order->total_amount, 2) }}</dd>
    </dl>
</section>
