@php
    $quantity = $cartData['products']->sum('quantity');
    $subtotal = $cartData['products']->sum('subtotal');

    $priceBreakdownRows = [
        [
            'left'  => 'Product' . ($quantity > 1 ? 's' : '') . ' (' . $quantity . ')',
            'right' => '$' . $subtotal,
        ],
        [
            'left' => 'Delivery price',
            'right' => '',
        ],
    ];

    $priceBreakdownCalc = [
        [
            'class' => 'price-breakdown-subtotal',
            'label' => 'Subtotal',
            'value' => $subtotal,
        ],
        [
            'class' => 'price-breakdown-total',
            'label' => 'Total',
            'value' => ''
        ]
    ]
@endphp

<div class="order-summary">
    <header class="h4 fw-semibold">Order summary</header>
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
        By clicking "check out", you're agreeing to our
        <a href="#">Privacy Policy</a>
    </span>
    <form action="{{ route('checkout.store') }}" method="POST" class="my-3">
        @csrf
        @auth
            @if ($addresses->isNotEmpty())
                <label for="cart-shipping-address" class="form-label">Shipping address</label>
                <select name="address_id" id="cart-shipping-address" class="form-select mb-3" required>
                    @foreach ($addresses as $address)
                        <option value="{{ $address->getKey() }}" @selected((string) old('address_id', $addresses->first()->getKey()) === (string) $address->getKey())>
                            {{ $address->recipient_name }} — {{ $address->address_line_1 }}, {{ $address->city }}{{ $address->is_default ? ' (Default)' : '' }}
                        </option>
                    @endforeach
                </select>
            @else
                <p class="small">Add a shipping address before checking out. <a href="{{ route('profile') }}#pane-billing">Manage addresses</a></p>
            @endif
        @else
            <p class="small"><a href="{{ route('login') }}">Sign in</a> to check out.</p>
        @endauth
        @if ($errors->any())
            <ul class="small text-danger" role="alert">
                @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        @endif
        <button type="submit" class="w-100 rounded-1 border-0 p-3" @disabled($quantity < 1 || $addresses->isEmpty())>
            Check out
        </button>
    </form>
</div>
