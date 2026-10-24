        @php
            $products = $cart['products'];
            $productCount = count($products);
        @endphp

        <div>
            <div>
                <h1 class="fw-bold my-3 my-lg-5">
                    <span>
                        Your cart
                        {{ $productCount === 0 ? 'is empty' : ' ' }}
                    </span>
                </h1>
            </div>
            <p data-cart-feedback class="small text-danger" role="status" aria-live="polite"></p>
            <div class="row d-flex flex-row-reverse">
                <div class="col-12 col-lg-4">
                    <x-cart.order-summary :data="$cart" />
                </div>
                <div class="col-12 col-lg-8">

                    @if ($productCount > 0)
                        <div class="accordion accordion-flush" data-cart-view>
                            <div class="accordion-item">

                                <header class="accordion-header">
                                    <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#cart" aria-expanded="true" aria-controls="cart">
                                        <span>
                                            {{ $productCount }}
                                            {{ $productCount > 1 ? 'items' : 'item' }}
                                            in total
                                        </span>
                                    </button>
                                </header>

                                <div id="cart" class="accordion-collapse collapse show">
                                    <div class="bg-white p-3">
                                        <div class="d-flex gap-2 align-items-center">
                                            <label for="cart-page-sort-field" class="small fw-semibold">Sort items by: </label>
                                            <div class="d-flex gap-2">
                                                <select
                                                    id="cart-page-sort-field"
                                                    data-cart-sort
                                                    class="form-select form-select-sm"
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

                            </div>
                        </div>
                    @else
                        <div class="text-center">
                            <a href="{{ route('products.index') }}">Hop on to shop now</a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
