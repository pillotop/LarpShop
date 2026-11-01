@php
    $cartCount = $products->sum('quantity');
    $cartSubtotal = $products->sum('subtotal');
@endphp

<section class="d-flex flex-column h-100 bg-1" data-cart-view aria-label="{{ __('cart.shopping') }}">
    <header class="drawer-header bg-white border-bottom px-3 py-3">
        <div>
            <div class="text-uppercase fg-3 small fw-semibold">{{ __('cart.selection') }}</div>
            <h2 class="h4 fw-bold mb-0">{{ __('cart.shopping') }} <span class="badge rounded-pill bg-subtle-primary fg-primary fs-md">{{ $cartCount }}</span></h2>
        </div>
        <button class="btn-close" type="button" data-bs-dismiss="drawer" aria-label="{{ __('cart.close') }}"></button>
    </header>

    @if ($products->isNotEmpty())
        <div class="bg-white border-bottom p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="cart-sort-field" class="small fw-semibold">{{ __('cart.sort_items') }}</label>
                <span class="small fg-3">{{ trans_choice('cart.products', $products->count(), ['count' => $products->count()]) }}</span>
            </div>
            <div class="d-flex gap-2">
                <select id="cart-sort-field" data-cart-sort class="form-control form-control-sm" aria-label="{{ __('cart.sort_label') }}">
                    <option value="added">{{ __('cart.sort.added') }}</option>
                    <option value="name">{{ __('cart.sort.name') }}</option>
                    <option value="price">{{ __('cart.sort.price') }}</option>
                    <option value="quantity">{{ __('cart.sort.quantity') }}</option>
                    <option value="subtotal">{{ __('cart.sort.subtotal') }}</option>
                    <option value="stock">{{ __('cart.sort.stock') }}</option>
                </select>
                <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('cart.sort_direction') }}">
                    <button type="button" class="btn btn-outline theme-primary active" data-cart-direction="asc" aria-pressed="true" aria-label="{{ __('cart.sort_ascending') }}" title="{{ __('cart.ascending') }}"><i class="bi bi-sort-up" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-outline theme-primary" data-cart-direction="desc" aria-pressed="false" aria-label="{{ __('cart.sort_descending') }}" title="{{ __('cart.descending') }}"><i class="bi bi-sort-down" aria-hidden="true"></i></button>
                </div>
            </div>
            <span class="visually-hidden" data-cart-sort-status role="status" aria-live="polite"></span>
        </div>

        <div class="flex-grow-1 overflow-auto p-3" style="min-height: 0;" data-cart-scroll>
            <div class="d-flex flex-column gap-3" data-cart-items>
                @foreach ($products as $item)
                    @php
                        $product = $item['product'];
                        $detailsId = 'cart-details-'.$item['cart_item_id'];
                    @endphp
                    <article class="card border-0 shadow-sm rounded-9 overflow-hidden" data-cart-item="{{ $item['cart_item_id'] }}"
                             data-sort-added="{{ $item['added_at'] ?? $item['cart_item_id'] }}" data-sort-name="{{ $product['name'] }}"
                             data-sort-price="{{ $product['price'] }}" data-sort-quantity="{{ $item['quantity'] }}"
                             data-sort-subtotal="{{ $item['subtotal'] }}" data-sort-stock="{{ $product['stock'] }}">
                        <div class="p-3">
                            <div class="d-flex gap-3">
                                <a href="{{ $product['url'] }}" class="flex-shrink-0">
                                    @if ($product['image_url'])
                                        <img src="{{ $product['image_url'] }}" alt="{{ $product['image_alt'] }}" loading="lazy"
                                             class="rounded-5 border bg-white object-fit-cover" style="width: 76px; height: 76px;">
                                    @else
                                        <span class="rounded-5 border bg-1 d-flex align-items-center justify-content-center fg-secondary" style="width: 76px; height: 76px;" role="img" aria-label="{{ __('cart.no_image') }}">
                                            <i class="bi bi-image fs-xl" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </a>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <a href="{{ $product['url'] }}" class="fw-semibold fg-body text-decoration-none d-block text-break lh-sm mb-2">{{ $product['name'] }}</a>
                                    <div class="small fg-3">{{ __('cart.each', ['price' => '$' . number_format($product['price'], 2)]) }}</div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                                        <span class="badge bg-1 fg-body border rounded-pill">{{ __('cart.quantity', ['count' => $item['quantity']]) }}</span>
                                        <strong>${{ number_format($item['subtotal'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion accordion-flush border-top">
                            <details id="{{ $detailsId }}" class="accordion-item border-0" data-cart-details="{{ $item['cart_item_id'] }}">
                                <summary class="accordion-header">
                                    {{ __('cart.details') }}
                                    <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                                </summary>
                                <div class="accordion-panel">
                                    <div class="accordion-body bg-1 small p-3">
                                        <p class="fg-secondary text-break mb-3" style="white-space: pre-line;">{{ $product['description'] ?: __('cart.no_description') }}</p>
                                        <dl class="row g-2 mb-3">
                                            <dt class="col-6 fg-3 fw-normal">{{ __('cart.reference') }}</dt><dd class="col-6 text-end mb-0">#{{ $product['product_id'] }}</dd>
                                            <dt class="col-6 fg-3 fw-normal">{{ __('cart.sort.stock') }}</dt><dd class="col-6 text-end mb-0">{{ $product['stock'] }}</dd>
                                            <dt class="col-6 fg-3 fw-normal">{{ __('cart.sort.price') }}</dt><dd class="col-6 text-end mb-0">${{ number_format($product['price'], 2) }}</dd>
                                        </dl>
                                        @if ($item['quantity'] > $product['stock'])
                                            <p class="fg-danger mb-3">{{ __('cart.exceeds_stock') }}</p>
                                        @endif
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <a href="{{ $product['url'] }}" class="text-decoration-none">{{ __('cart.view_product') }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                                            <button class="btn btn-sm btn-outline theme-danger" type="button" data-cart-remove="{{ $product['product_id'] }}" aria-label="{{ __('cart.remove_product', ['name' => $product['name']]) }}">
                                                <i class="bi bi-trash me-1" aria-hidden="true"></i>{{ __('cart.remove') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <footer class="bg-white border-top p-3">
            <p data-cart-feedback class="small fg-danger mb-2" role="status" aria-live="polite"></p>
            <div class="d-flex justify-content-between align-items-center">
                <div><span class="fw-semibold">{{ __('cart.sort.subtotal') }}</span><div class="small fg-3">{{ trans_choice('cart.items_in_cart', $cartCount, ['count' => $cartCount]) }}</div></div>
                <strong class="fs-lg">${{ number_format($cartSubtotal, 2) }}</strong>
            </div>
            <a href="{{ route('cart.index') }}" class="btn btn-outline theme-primary w-100 rounded-pill mt-3">{{ __('cart.to_checkout') }}</a>
        </footer>
    @else
        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center p-4">
            <span class="rounded-circle bg-white shadow-sm p-4 mb-3"><i class="bi bi-bag fs-3xl fg-primary" aria-hidden="true"></i></span>
            <h3 class="h5 fw-bold">{{ __('cart.empty') }}</h3>
            <p class="fg-3 small">{{ __('cart.empty_help') }}</p>
            <a href="{{ route('products.index') }}" class="btn btn-solid theme-primary rounded-pill px-4">{{ __('cart.explore') }}</a>
        </div>
    @endif
</section>
