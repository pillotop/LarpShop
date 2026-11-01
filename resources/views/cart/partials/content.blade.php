        @php
            $products = $cart['products'];
            $productCount = count($products);
        @endphp

        <div>
            <div>
                <h1 class="fw-bold my-3 lg:my-5">
                    <span>
                        Your cart
                        {{ $productCount === 0 ? 'is empty' : ' ' }}
                    </span>
                </h1>
            </div>
            <p data-cart-feedback class="small fg-danger" role="status" aria-live="polite"></p>
            <div class="row d-flex flex-row-reverse">
                <div class="col-12 lg:col-4">
                    <x-cart.order-summary :data="$cart" />
                </div>
                <div class="col-12 lg:col-8">

                    @if ($productCount > 0)
                        <div class="accordion accordion-flush" data-cart-view>
                            <details id="cart" class="accordion-item" open>
                                <summary class="accordion-header">
                                    <span>
                                        {{ $productCount }}
                                        {{ $productCount > 1 ? 'items' : 'item' }}
                                        in total
                                    </span>
                                    <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                                </summary>
                                <div class="accordion-panel">
                                    <div class="bg-white p-3">
                                        <div class="d-flex gap-2 align-items-center">
                                            <label for="cart-page-sort-field" class="small fw-semibold">Sort items by: </label>
                                            <div class="d-flex gap-2">
                                                <select
                                                    id="cart-page-sort-field"
                                                    data-cart-sort
                                                    class="form-control form-control-sm"
                                                    aria-label="Sort cart items by"
                                                >
                                                    <option value="added">Date added</option>
                                                    <option value="name">Product name</option>
                                                    <option value="price">Unit price</option>
                                                    <option value="quantity">Quantity</option>
                                                    <option value="subtotal">Subtotal</option>
                                                    <option value="stock">Available stock</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <ul class="list-unstyled" data-cart-items>
                                        @foreach ($products as $product)
                                            <li>
                                                <x-cart.product-card :data="$product" />
                                                @if (!$loop->last)
                                                    <hr/>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </details>
                        </div>
                    @else
                        <div class="text-center">
                            <a href="{{ route('products.index') }}">Hop on to shop now</a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
