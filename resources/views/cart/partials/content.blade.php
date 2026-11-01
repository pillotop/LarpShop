        @php
            $products = $cart['products'];
            $productCount = count($products);
        @endphp

        <div>
            <div>
                <h1 class="fw-bold my-3 lg:my-5">
                    <span>
                        {{ $productCount === 0 ? __('cart.empty') : __('cart.heading') }}
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
                                        {{ trans_choice('cart.items_total', $productCount, ['count' => $productCount]) }}
                                    </span>
                                    <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                                </summary>
                                <div class="accordion-panel">
                                    <div class="bg-white p-3">
                                        <div class="d-flex gap-2 align-items-center">
                                            <label for="cart-page-sort-field" class="small fw-semibold">{{ __('cart.sort_by') }} </label>
                                            <div class="d-flex gap-2">
                                                <select
                                                    id="cart-page-sort-field"
                                                    data-cart-sort
                                                    class="form-control form-control-sm"
                                                    aria-label="{{ __('cart.sort_label') }}"
                                                >
                                                    <option value="added">{{ __('cart.sort.added') }}</option>
                                                    <option value="name">{{ __('cart.sort.name') }}</option>
                                                    <option value="price">{{ __('cart.sort.price') }}</option>
                                                    <option value="quantity">{{ __('cart.sort.quantity') }}</option>
                                                    <option value="subtotal">{{ __('cart.sort.subtotal') }}</option>
                                                    <option value="stock">{{ __('cart.sort.stock') }}</option>
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
                            <a href="{{ route('products.index') }}">{{ __('cart.shop_now') }}</a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
