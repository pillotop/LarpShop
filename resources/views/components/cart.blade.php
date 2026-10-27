@php
    $cartCount = $products->sum('quantity');
    $cartSubtotal = $products->sum('subtotal');
@endphp

<section class="d-flex flex-column h-100 bg-light" data-cart-view aria-label="Shopping cart">
    <header class="offcanvas-header bg-white border-bottom px-3 py-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold">Your selection</div>
            <h2 class="h4 fw-bold mb-0">Shopping cart <span class="badge rounded-pill bg-primary-subtle text-primary fs-6">{{ $cartCount }}</span></h2>
        </div>
        <button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="Close shopping cart"></button>
    </header>

    @if ($products->isNotEmpty())
        <div class="bg-white border-bottom p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="cart-sort-field" class="small fw-semibold">Sort items</label>
                <span class="small text-muted">{{ $products->count() }} {{ $products->count() === 1 ? 'product' : 'products' }}</span>
            </div>
            <div class="d-flex gap-2">
                <select id="cart-sort-field" data-cart-sort class="form-select form-select-sm" aria-label="Sort cart items by">
                    <option value="added">Date added</option>
                    <option value="name">Product name</option>
                    <option value="price">Unit price</option>
                    <option value="quantity">Quantity</option>
                    <option value="subtotal">Subtotal</option>
                    <option value="stock">Available stock</option>
                </select>
                <div class="btn-group btn-group-sm" role="group" aria-label="Sort direction">
                    <button type="button" class="btn btn-outline-primary active" data-cart-direction="asc" aria-pressed="true" aria-label="Sort ascending" title="Ascending"><i class="bi bi-sort-up" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-outline-primary" data-cart-direction="desc" aria-pressed="false" aria-label="Sort descending" title="Descending"><i class="bi bi-sort-down" aria-hidden="true"></i></button>
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
                    <article class="card border-0 shadow-sm rounded-4 overflow-hidden" data-cart-item="{{ $item['cart_item_id'] }}"
                             data-sort-added="{{ $item['added_at'] ?? $item['cart_item_id'] }}" data-sort-name="{{ $product['name'] }}"
                             data-sort-price="{{ $product['price'] }}" data-sort-quantity="{{ $item['quantity'] }}"
                             data-sort-subtotal="{{ $item['subtotal'] }}" data-sort-stock="{{ $product['stock'] }}">
                        <div class="p-3">
                            <div class="d-flex gap-3">
                                <a href="{{ $product['url'] }}" class="flex-shrink-0">
                                    @if ($product['image_url'])
                                        <img src="{{ $product['image_url'] }}" alt="{{ $product['image_alt'] }}" loading="lazy"
                                             class="rounded-3 border bg-white object-fit-cover" style="width: 76px; height: 76px;">
                                    @else
                                        <span class="rounded-3 border bg-light d-flex align-items-center justify-content-center text-secondary" style="width: 76px; height: 76px;" role="img" aria-label="No product image available">
                                            <i class="bi bi-image fs-3" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </a>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <a href="{{ $product['url'] }}" class="fw-semibold text-dark text-decoration-none d-block text-break lh-sm mb-2">{{ $product['name'] }}</a>
                                    <div class="small text-muted">${{ number_format($product['price'], 2) }} each</div>
                                    <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                                        <span class="badge bg-light text-dark border rounded-pill">Qty {{ $item['quantity'] }}</span>
                                        <strong>${{ number_format($item['subtotal'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion accordion-flush border-top">
                            <div class="accordion-item border-0">
                                <h3 class="accordion-header" id="{{ $detailsId }}-heading">
                                    <button class="accordion-button collapsed small py-2 px-3 bg-white shadow-none" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#{{ $detailsId }}" aria-expanded="false" aria-controls="{{ $detailsId }}">
                                        Product details
                                    </button>
                                </h3>
                                <div id="{{ $detailsId }}" class="accordion-collapse collapse" aria-labelledby="{{ $detailsId }}-heading" data-cart-details="{{ $item['cart_item_id'] }}">
                                    <div class="accordion-body bg-light small p-3">
                                        <p class="text-secondary text-break mb-3" style="white-space: pre-line;">{{ $product['description'] ?: 'No description available.' }}</p>
                                        <dl class="row g-2 mb-3">
                                            <dt class="col-6 text-muted fw-normal">Product reference</dt><dd class="col-6 text-end mb-0">#{{ $product['product_id'] }}</dd>
                                            <dt class="col-6 text-muted fw-normal">Available stock</dt><dd class="col-6 text-end mb-0">{{ $product['stock'] }}</dd>
                                            <dt class="col-6 text-muted fw-normal">Unit price</dt><dd class="col-6 text-end mb-0">${{ number_format($product['price'], 2) }}</dd>
                                        </dl>
                                        @if ($item['quantity'] > $product['stock'])
                                            <p class="text-danger mb-3">The selected quantity now exceeds available stock.</p>
                                        @endif
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <a href="{{ $product['url'] }}" class="text-decoration-none">View product <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-cart-remove="{{ $product['product_id'] }}" aria-label="Remove {{ $product['name'] }} from cart">
                                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <footer class="bg-white border-top p-3">
            <p data-cart-feedback class="small text-danger mb-2" role="status" aria-live="polite"></p>
            <div class="d-flex justify-content-between align-items-center">
                <div><span class="fw-semibold">Subtotal</span><div class="small text-muted">{{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }} in your cart</div></div>
                <strong class="fs-4">${{ number_format($cartSubtotal, 2) }}</strong>
            </div>
            <a href="{{ route('cart.index') }}" class="btn btn-outline-primary w-100 rounded-pill mt-3">To Checkout</a>
        </footer>
    @else
        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center p-4">
            <span class="rounded-circle bg-white shadow-sm p-4 mb-3"><i class="bi bi-bag fs-1 text-primary" aria-hidden="true"></i></span>
            <h3 class="h5 fw-bold">Your cart is empty</h3>
            <p class="text-muted small">Find something you love. Your selected products will appear here.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Explore products</a>
        </div>
    @endif
</section>
