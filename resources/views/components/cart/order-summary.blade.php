@props([
    'data'
])

@php
    $quantity = $data['products']->sum('quantity');
    $subtotal = $data['products']->sum('subtotal');

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
        <button class="w-100 rounded-1 border-0 p-3">
            Check out
        </button>
    </form>
</div>
