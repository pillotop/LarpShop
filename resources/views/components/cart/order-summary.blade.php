@php
    $quantity = $cartData['products']->sum('quantity');
    $subtotal = (float) $cartData['products']->sum('subtotal');
    $formattedSubtotal = '$' . number_format($subtotal, 2);
@endphp

<div class="order-summary" data-free-label="{{ __('checkout.free') }}">
    <header class="h4 fw-semibold">{{ __('checkout.summary') }}</header>
    <div class="price-breakdown">
        <div class="d-flex flex-nowrap justify-content-between my-1">
            <span data-summary-products-label>{{ trans_choice('checkout.summary_products', $quantity, ['count' => $quantity]) }}</span>
            <span data-summary-products-amount>{{ $formattedSubtotal }}</span>
        </div>
        <div class="d-flex flex-nowrap justify-content-between my-1">
            <span>{{ __('checkout.delivery_price') }}</span>
            <span data-summary-shipping>{{ __('checkout.free') }}</span>
        </div>
    </div>
    <hr class="my-3 price-breakdown-divider">
    <dl class="price-breakdown-calc">
        <div class="d-flex flex-nowrap justify-content-between price-breakdown-subtotal">
            <dt class="text-capitalize price-breakdown-calc__summary">
                {{ __('cart.sort.subtotal') }}
            </dt>
            <dd class="h2 price-breakdown-calc__value" data-summary-subtotal>
                {{ $formattedSubtotal }}
            </dd>
        </div>
        <div class="d-flex flex-nowrap justify-content-between price-breakdown-total">
            <dt class="text-capitalize price-breakdown-calc__summary">
                {{ __('checkout.total') }}
            </dt>
            <dd class="h2 price-breakdown-calc__value" data-summary-total>
                {{ $formattedSubtotal }}
            </dd>
        </div>
    </dl>
    <span class="small">
        {{ __('checkout.privacy_intro') }}
        <a href="{{ route('privacy') }}">{{ __('checkout.privacy') }}</a>
    </span>
    <form action="{{ route('checkout.store') }}" method="POST" class="my-3" data-checkout-form>
        @csrf
        <div data-checkout-selected-inputs>
            @foreach ($cartData['products'] as $item)
                <input type="hidden" name="selected_cart_item_ids[]" value="{{ $item['cart_item_id'] }}">
            @endforeach
        </div>
        <div class="mb-3">
            <label for="cart-shipping-method" class="form-label">{{ __('checkout.shipping_methods.label') }}</label>
            <select name="shipping_method" id="cart-shipping-method" class="form-control" data-shipping-method-select>
                <option value="home_delivery" data-fee="0">{{ __('checkout.shipping_methods.home_delivery') }}</option>
                <option value="pickup" data-fee="0">{{ __('checkout.shipping_methods.pickup') }}</option>
                <option value="express" data-fee="9.99">{{ __('checkout.shipping_methods.express') }}</option>
            </select>
        </div>
        @auth
            <div data-address-select-wrap @if ($addresses->isEmpty()) hidden @endif>
                <label for="cart-shipping-address" class="form-label">{{ __('checkout.address.title') }}</label>
                <select name="address_id" id="cart-shipping-address" class="form-control mb-2" @required($addresses->isNotEmpty())>
                    @foreach ($addresses as $address)
                        <option value="{{ $address->getKey() }}" @selected((string) old('address_id', $addresses->first()->getKey()) === (string) $address->getKey())>
                            {{ $address->recipient_name }} — {{ $address->address_line_1 }}, {{ $address->city }}{{ $address->is_default ? __('checkout.address.default') : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <p class="small mb-2" data-no-address-hint @if ($addresses->isNotEmpty()) hidden @endif>
                {{ __('checkout.address.add_first') }}
            </p>
            <button type="button" class="btn btn-sm btn-outline theme-primary rounded-pill mb-3" data-open-inline-address>
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('checkout.address.add_new') }}
            </button>
        @else
            <p class="small"><a href="{{ route('login') }}">{{ __('common.login') }}</a> {{ __('checkout.sign_in_suffix') }}</p>
        @endauth
        @if ($errors->any())
            <ul class="small fg-danger" role="alert">
                @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        @endif
        <button type="submit" class="w-100 rounded-3 border-0 p-3 btn btn-solid theme-primary fw-semibold" data-checkout-submit @disabled($quantity < 1 || $addresses->isEmpty())>
            {{ __('checkout.checkout') }}
        </button>
    </form>

    @auth
        <dialog id="inline-address-dialog" class="rounded-5 border-0 shadow-lg p-0" style="width: min(94vw, 560px);">
            <div class="d-flex justify-content-between align-items-center border-bottom px-4 py-3 bg-1">
                <h3 class="h5 fw-bold mb-0">{{ __('checkout.address.add_new') }}</h3>
                <button type="button" class="btn-close" data-close-inline-address aria-label="{{ __('common.close') }}"></button>
            </div>
            <form action="{{ route('addresses.store') }}" method="POST" class="p-4" data-inline-address-form>
                @csrf
                <div class="alert theme-danger small d-none mb-3" data-inline-address-error role="alert"></div>
                <div class="row g-3">
                    <div class="col-12 md:col-6">
                        <label class="form-label small fw-semibold" for="inline-recipient-name">{{ __('profile.address.recipient') }} *</label>
                        <input id="inline-recipient-name" name="recipient_name" type="text" maxlength="100" class="form-control" required>
                    </div>
                    <div class="col-12 md:col-6">
                        <label class="form-label small fw-semibold" for="inline-phone-number">{{ __('profile.phone') }}</label>
                        <input id="inline-phone-number" name="phone_number" type="tel" maxlength="30" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="inline-address-line-1">{{ __('profile.address.line1') }} *</label>
                        <input id="inline-address-line-1" name="address_line_1" type="text" maxlength="255" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="inline-address-line-2">{{ __('profile.address.line2') }}</label>
                        <input id="inline-address-line-2" name="address_line_2" type="text" maxlength="255" class="form-control">
                    </div>
                    <div class="col-12 md:col-6">
                        <label class="form-label small fw-semibold" for="inline-city">{{ __('profile.address.city') }} *</label>
                        <input id="inline-city" name="city" type="text" maxlength="100" class="form-control" required>
                    </div>
                    <div class="col-12 md:col-6">
                        <label class="form-label small fw-semibold" for="inline-region">{{ __('profile.address.region') }}</label>
                        <input id="inline-region" name="region" type="text" maxlength="100" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="inline-postal-code">{{ __('profile.address.postal') }}</label>
                        <input id="inline-postal-code" name="postal_code" type="text" maxlength="30" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="inline-country-code">{{ __('profile.address.country') }} *</label>
                        <input id="inline-country-code" name="country_code" type="text" maxlength="2" minlength="2" placeholder="US / VN" class="form-control text-uppercase" required>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline theme-secondary rounded-pill px-3" data-close-inline-address>{{ __('common.close') }}</button>
                    <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4 fw-semibold">{{ __('checkout.address.save') }}</button>
                </div>
            </form>
        </dialog>
    @endauth
</div>
