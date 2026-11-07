@php
    $quantity = $cartData['products']->sum('quantity');
    $subtotal = $cartData['products']->sum('subtotal');

    $priceBreakdownRows = [
        [
            'left'  => trans_choice('checkout.summary_products', $quantity, ['count' => $quantity]),
            'right' => '$' . $subtotal,
        ],
        [
            'left' => __('checkout.delivery_price'),
            'right' => '',
        ],
    ];

    $priceBreakdownCalc = [
        [
            'class' => 'price-breakdown-subtotal',
            'label' => __('cart.sort.subtotal'),
            'value' => $subtotal,
        ],
        [
            'class' => 'price-breakdown-total',
            'label' => __('checkout.total'),
            'value' => ''
        ]
    ]
@endphp

<div class="order-summary">
    <header class="h4 fw-semibold">{{ __('checkout.summary') }}</header>
    <div class="price-breakdown">
        @foreach ($priceBreakdownRows as $row)
            <div class="d-flex flex-nowrap justify-content-between my-1">
                <span>{{ $row['left'] }}</span>
                <span>{{ $row['right'] }}</span>
            </div>
        @endforeach
    </div>
    <hr class="my-3 price-breakdown-divider">
    <dl class="price-breakdown-calc">
        @foreach ($priceBreakdownCalc as $calc)
            <div class="d-flex flex-nowrap justify-content-between {{ $calc['class'] }}">
                <dt class="text-capitalize price-breakdown-calc__summary">
                    {{ $calc['label'] }}
                </dt>

                <dd class="h2 price-breakdown-calc__value">
                    {{ $calc['value'] }}
                </dd>
            </div>
        @endforeach
    </dl>
    <span class="small">
        {{ __('checkout.privacy_intro') }}
        <a href="#">{{ __('checkout.privacy') }}</a>
    </span>
    <form action="{{ route('checkout.store') }}" method="POST" class="my-3">
        @csrf
        @auth
            @if ($addresses->isNotEmpty())
                <label for="cart-shipping-address" class="form-label">{{ __('checkout.address.title') }}</label>
                <select name="address_id" id="cart-shipping-address" class="form-control mb-3" required>
                    @foreach ($addresses as $address)
                        <option value="{{ $address->getKey() }}" @selected((string) old('address_id', $addresses->first()->getKey()) === (string) $address->getKey())>
                            {{ $address->recipient_name }} — {{ $address->address_line_1 }}, {{ $address->city }}{{ $address->is_default ? __('checkout.address.default') : '' }}
                        </option>
                    @endforeach
                </select>
            @else
                <p class="small">{{ __('checkout.address.add_first') }} <a href="{{ route('profile') }}#pane-billing">{{ __('checkout.address.manage') }}</a></p>
            @endif
        @else
            <p class="small"><a href="{{ route('login') }}">{{ __('common.login') }}</a> {{ __('checkout.sign_in_suffix') }}</p>
        @endauth
        @if ($errors->any())
            <ul class="small fg-danger" role="alert">
                @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        @endif
        <button type="submit" class="w-100 rounded-3 border-0 p-3" @disabled($quantity < 1 || $addresses->isEmpty())>
            {{ __('checkout.checkout') }}
        </button>
    </form>
</div>
