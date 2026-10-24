@props([
    'data'
])

@php
    $product = $data['product'];
    $detailsId = 'cart-details-'.$data['cart_item_id'];
@endphp

<div
    class="card border-0 overflow-hidden"
    data-cart-item="{{ $data['cart_item_id'] }}"
    data-sort-added="{{ $data['added_at'] ?? $data['cart_item_id'] }}"
    data-sort-name="{{ $product['name'] }}"
    data-sort-price="{{ $product['price'] }}"
    data-sort-quantity="{{ $data['quantity'] }}"
    data-sort-subtotal="{{ $data['subtotal'] }}"
    data-sort-stock="{{ $product['stock'] }}"
>
    @once
        <style>
            [data-cart-quantity] [data-quantity-counter] {
                width: 2.5rem;
                flex: 0 0 2.5rem;
                appearance: textfield;
                -moz-appearance: textfield;
            }
            [data-cart-quantity] input::-webkit-inner-spin-button,
            [data-cart-quantity] input::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            [data-cart-quantity] .btn:hover {
                background-color: #f1f3f5;
            }
            .cart-item-remove {
                background-color: transparent;
            }
            .cart-item-remove:hover,
            .cart-item-remove:focus-visible {
                background-color: #f1f3f5;
            }
        </style>
    @endonce
    <div class="p-3">
        <div class="d-flex gap-3">
            <a href="{{ $product['url'] }}" class="flex-shrink-0">
                @if ($product['image_url'])
                    <img
                        src="{{ $product['image_url'] }}"
                        alt="{{ $product['image_alt'] }}"
                        loading="lazy"
                        class="rounded-1 border-0 bg-white object-fit-cover"
                        {{-- change this ples --}}
                        style="width: 114px; height: 114px;"
                    />
                @else
                    <span class="rounded-3 border bg-light d-flex align-items-center justify-content-center text-secondary" style="width: 76px; height: 76px;" role="img" aria-label="No product image available">
                        <i class="bi bi-image fs-3" aria-hidden="true"></i>
                    </span>
                @endif
            </a>
            <div class="flex-grow-1" style="min-with: 0;">
                <a
                    href="{{ $product['url'] }}"
                    class="fw-semibold text-dark text-decoration-none d-block text-break lh-sm mb-2"
                >
                    {{ $product['name'] }}
                </a>
                <div class="small text-muted">
                    ${{ number_format($product['price'], 2) }} each
                </div>
                <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                    <div>
                        <div class="quantity-picker d-inline-flex align-items-center bg-white border rounded-2"
                             data-cart-quantity data-product-id="{{ $product['product_id'] }}"
                             data-set-cart-url="{{ route('cart.store') }}">
                            @csrf
                            <button id="increment-{{ $data['cart_item_id'] }}" data-quantity-increment type="button"
                                    class="btn btn-sm border-0 rounded-0 fw-semibold px-2" aria-label="Increase quantity">+</button>
                            <input id="counter-{{ $data['cart_item_id'] }}" data-quantity-counter type="number"
                                   class="form-control form-control-sm border-0 rounded-0 text-center px-1"
                                   value="{{ $data['quantity'] }}" min="0" step="1" aria-label="Quantity for {{ $product['name'] }}">
                            <button id="decrement-{{ $data['cart_item_id'] }}" data-quantity-decrement type="button"
                                    class="btn btn-sm border-0 rounded-0 fw-semibold px-2" aria-label="Decrease quantity">−</button>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm cart-item-remove border-0 rounded-2 ms-1"
                            data-cart-remove="{{ $product['product_id'] }}"
                            aria-label="Remove {{ $product['name'] }} from cart"
                        >
                            <i class="bi bi-trash me-1" aria-hidden="true"></i>
                        </button>
                    </div>
                    <strong>${{ number_format($data['subtotal'], 2) }}</strong>
                </div>
                <div class="cart-feedback small mt-2 visually-hidden" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>
