<section
    {{ $attributes->class(['card border-0 shadow-sm rounded-4 p-4']) }}
    aria-label="Order summary"
>
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">
            Order summary
        </h2>
        <span class="badge text-bg-secondary">
            {{ ucfirst($order->status->value) }}
        </span>
    </div>
    <p class="small text-muted">Order #{{ $order->getKey() }} · {{ $order->details->sum('quantity') }} item(s)</p>
    <ul class="list-unstyled mb-0">
        @forelse ($order->details as $detail)
            <li class="border-bottom py-3">
                <div class="d-flex justify-content-between gap-3">
                    <span class="fw-semibold">{{ $detail->product?->name ?? 'Unavailable product' }}</span>
                    <span class="text-nowrap">${{ number_format((float) $detail->unit_price, 2) }} each</span>
                </div>
                <span class="small text-muted">Quantity: {{ $detail->quantity }}</span>
            </li>
        @empty
            <li class="text-muted py-3">This order has no items.</li>
        @endforelse
    </ul>
    <dl class="d-flex justify-content-between align-items-baseline gap-3 mt-4 mb-0">
        <dt>Total</dt>
        <dd class="h4 fw-bold mb-0">${{ number_format((float) $order->total_amount, 2) }}</dd>
    </dl>
</section>
