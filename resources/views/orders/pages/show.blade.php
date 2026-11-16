@extends('layouts.app')

@section('title', __('orders.details'))

@section('head')
    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
    ])
@endsection

@section('content')
    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    @php
        $isPaid = $order->status === \App\Enums\OrderStatus::Paid;
        $maxRank = $order->details->map(fn ($d) => $d->fulfillment_status->rank())->max() ?? 0;
        $allDelivered = $order->details->isNotEmpty()
            && $order->details->every(fn ($d) => $d->fulfillment_status === \App\Enums\FulfillmentStatus::Delivered);
        $hasShipped = $order->details->contains(fn ($d) => $d->fulfillment_status === \App\Enums\FulfillmentStatus::Shipped);
        $paidPayment = $order->payments->firstWhere('status', \App\Enums\PaymentStatus::Succeeded);
        $earliestShippedAt = $order->details->pluck('shipped_at')->filter()->min();
        $latestDeliveredAt = $allDelivered ? $order->details->pluck('delivered_at')->filter()->max() : null;
        $shippingAddress = $order->addresses->firstWhere('type', 'shipping');

        $steps = [
            [
                'key' => 'placed',
                'icon' => 'bi-receipt',
                'label' => __('orders.timeline.placed'),
                'time' => $order->created_at?->format('Y-m-d H:i'),
                'done' => true,
            ],
            [
                'key' => 'paid',
                'icon' => 'bi-credit-card-check',
                'label' => __('orders.timeline.paid'),
                'time' => $paidPayment?->updated_at?->format('Y-m-d H:i'),
                'done' => $isPaid,
            ],
            [
                'key' => 'processing',
                'icon' => 'bi-box-seam',
                'label' => __('orders.timeline.processing'),
                'time' => null,
                'done' => $isPaid && $maxRank >= \App\Enums\FulfillmentStatus::Processing->rank(),
            ],
            [
                'key' => 'shipped',
                'icon' => 'bi-truck',
                'label' => __('orders.timeline.shipped'),
                'time' => $earliestShippedAt?->format('Y-m-d H:i'),
                'done' => $isPaid && $maxRank >= \App\Enums\FulfillmentStatus::Shipped->rank(),
            ],
            [
                'key' => 'delivered',
                'icon' => 'bi-check2-circle',
                'label' => __('orders.timeline.delivered'),
                'time' => $latestDeliveredAt?->format('Y-m-d H:i'),
                'done' => $isPaid && $allDelivered,
            ],
        ];
    @endphp

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ __('orders.number', ['id' => $order->getKey()]) }}</h1>
                <p class="fg-3 mb-0">{{ __('orders.status', ['status' => __('checkout.order_status.' . $order->status->value)]) }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('profile') }}#pane-purchases" class="btn btn-outline theme-secondary rounded-pill px-3">{{ __('orders.back_to_purchases') }}</a>
                <a href="{{ route('orders.index') }}" class="btn btn-outline theme-primary rounded-pill px-3">{{ __('orders.history') }}</a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert theme-success rounded-4 p-3 mb-4" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert theme-danger rounded-4 p-3 mb-4" role="alert">{{ $errors->first() }}</div>
        @endif
        @if ($isPaid)
            <div class="alert theme-success rounded-4 p-3 mb-4">{{ __('orders.confirmed') }}</div>
        @endif

        @if (! in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Expired], true))
            <section class="card border-0 shadow-sm rounded-9 p-4 mb-4 bg-white" aria-label="{{ __('orders.timeline.title') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h2 class="h5 fw-bold mb-0">{{ __('orders.timeline.title') }}</h2>
                    @if ($isPaid && $hasShipped)
                        <form action="{{ route('orders.confirm-delivery', $order) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-solid theme-primary btn-sm rounded-pill px-3 fw-semibold">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ __('orders.confirm_delivery') }}
                            </button>
                        </form>
                    @endif
                </div>
                <div class="order-timeline">
                    @foreach ($steps as $step)
                        <div @class([
                            'order-timeline-step d-flex align-items-start gap-2',
                            'is-done' => $step['done'],
                        ])>
                            <i class="bi {{ $step['icon'] }} fs-5 {{ $step['done'] ? 'fg-success' : 'fg-3' }}" aria-hidden="true"></i>
                            <div>
                                <div class="small fw-semibold {{ $step['done'] ? 'fg-body' : 'fg-3' }}">{{ $step['label'] }}</div>
                                @if ($step['time'])
                                    <div class="small fg-3" style="font-size: 0.75rem;">{{ $step['time'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="row g-4">
            <div class="col-12 lg:col-8 d-flex flex-column gap-4">
                <section class="card border-0 shadow-sm rounded-9 p-4 bg-white">
                    <h2 class="h5 fw-bold mb-3">{{ __('orders.items_title') }}</h2>
                    <ul class="list-unstyled mb-0">
                        @foreach ($order->details as $detail)
                            @php
                                $product = $detail->product;
                                $imgPath = $product?->images->first()?->path;
                                $imgUrl = $imgPath
                                    ? (\Illuminate\Support\Str::startsWith($imgPath, ['http://', 'https://'])
                                        ? $imgPath
                                        : asset('storage/' . ltrim($imgPath, '/')))
                                    : asset('images/product-placeholder.jpg');
                            @endphp
                            <li @class(['d-flex flex-wrap gap-3 py-3', 'border-top' => ! $loop->first])>
                                <img src="{{ $imgUrl }}" alt="{{ $product?->name ?? '' }}" class="rounded-3 border object-fit-cover flex-shrink-0" style="width: 72px; height: 72px;">
                                <div class="flex-grow-1 min-w-0">
                                    @if ($product)
                                        <a href="{{ route('products.show', [$product, $product->slug]) }}" class="fw-semibold fg-body text-decoration-none d-block">{{ $product->name }}</a>
                                    @else
                                        <span class="fw-semibold fg-3">{{ __('checkout.unavailable_product') }}</span>
                                    @endif
                                    @if ($detail->variant_name)
                                        <span class="badge bg-1 fg-body border rounded-pill mt-1">{{ __('cart.variant', ['name' => $detail->variant_name]) }}</span>
                                    @endif
                                    @if ($product?->vendor)
                                        <div class="small fg-3 mt-1">{{ __('profile.purchases.sold_by', ['store' => $product->vendor->store_name]) }}</div>
                                    @endif
                                    <div class="small mt-1">{{ __('profile.purchases.quantity_price', ['count' => $detail->quantity, 'price' => '$' . number_format((float) $detail->unit_price, 2)]) }}</div>
                                    @if ($isPaid)
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                            <span class="badge bg-info">{{ $detail->fulfillment_status->label() }}</span>
                                            @if ($detail->shipped_at)
                                                <span class="small fg-3">{{ __('orders.shipped_at', ['date' => $detail->shipped_at->format('Y-m-d H:i')]) }}</span>
                                            @endif
                                            @if ($detail->delivered_at)
                                                <span class="small fg-success">{{ __('orders.delivered_at', ['date' => $detail->delivered_at->format('Y-m-d H:i')]) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if ($product && in_array($detail->product_id, $reviewableProductIds ?? [], true))
                                    <div class="align-self-center">
                                        <a href="{{ route('products.show', [$product, $product->slug]) }}#product-reviews" class="btn btn-sm btn-solid theme-primary rounded-pill px-3">
                                            {{ __('profile.purchases.write_review') }}
                                        </a>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($order->payments->isNotEmpty() || $shippingAddress)
                    <div class="row g-4">
                        @if ($shippingAddress)
                            <div class="col-12 md:col-6">
                                <section class="card border-0 shadow-sm rounded-9 p-4 bg-white h-100">
                                    <h2 class="h6 fw-bold mb-2">{{ __('orders.shipping_address') }}</h2>
                                    <address class="small fg-3 mb-0">
                                        <strong class="fg-body">{{ $shippingAddress->recipient_name }}</strong><br>
                                        {{ $shippingAddress->address_line_1 }}<br>
                                        @if ($shippingAddress->address_line_2) {{ $shippingAddress->address_line_2 }}<br> @endif
                                        {{ $shippingAddress->city }} {{ $shippingAddress->region }} {{ $shippingAddress->postal_code }}<br>
                                        {{ $shippingAddress->country_code }}
                                        @if ($shippingAddress->phone_number)<br>{{ $shippingAddress->phone_number }}@endif
                                    </address>
                                </section>
                            </div>
                        @endif
                        <div class="col-12 md:col-6">
                            <section class="card border-0 shadow-sm rounded-9 p-4 bg-white h-100">
                                <h2 class="h6 fw-bold mb-2">{{ __('checkout.payment.status') }}</h2>
                                @foreach ($order->payments as $payment)
                                    <p class="small mb-1">{{ __('orders.payment', ['id' => $payment->getKey(), 'provider' => $payment->provider, 'status' => __('checkout.payment_status.' . $payment->status->value)]) }}</p>
                                @endforeach
                                <a href="{{ route('checkout.show', $order) }}" class="small text-decoration-none mt-2 d-inline-block">{{ __('orders.shipping_payment') }}</a>
                            </section>
                        </div>
                    </div>
                @else
                    <div>
                        <a href="{{ route('checkout.show', $order) }}" class="text-decoration-none">{{ __('orders.shipping_payment') }}</a>
                    </div>
                @endif
            </div>

            <div class="col-12 lg:col-4 d-flex flex-column gap-3">
                <x-checkout.order-summary :data="$order" class="bg-white" />

                @if ($order->status === \App\Enums\OrderStatus::Pending)
                    <a href="{{ route('checkout.show', $order) }}" class="btn btn-solid theme-primary w-100 rounded-pill py-2 fw-semibold">
                        {{ __('profile.purchases.complete_payment') }}
                    </a>
                    <form action="{{ route('orders.cancel', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline theme-danger w-100 rounded-pill py-2">
                            {{ __('orders.cancel') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </main>

    <x-company-footer />
    <x-side-panel />
@endsection
