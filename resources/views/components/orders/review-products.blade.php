@props(['order', 'reviewableProductIds'])

<ul class="list-unstyled mb-0">
    @foreach ($order->details as $detail)
        @if ($detail->product)
            <li class="border-top py-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>{{ $detail->product->name }}</span>
                    <span class="text-muted">Quantity: {{ $detail->quantity }}</span>
                </div>

                @if ($order->status === \App\Enums\OrderStatus::Paid && in_array($detail->product_id, $reviewableProductIds))
                    <x-review-form
                        :product="$detail->product"
                        :id="'review-form-order-' . $order->getKey() . '-detail-' . $detail->getKey()"
                    />
                @endif
            </li>
        @endif
    @endforeach
</ul>
