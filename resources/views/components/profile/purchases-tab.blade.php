@props([
    'orders',
    'filters',
    'reviewableProductIds' => [],
])

@php
    $anchor = '#' . \App\Queries\Orders\PurchaseHistoryQuery::FRAGMENT;
    $hasActiveFilters = $filters['q'] !== '' || $filters['filter'] !== 'all' || $filters['period'] !== 'all';

    // Query-string for a filter link: keep the other filters, reset pagination, omit defaults.
    $filterUrl = function (array $overrides = []) use ($filters, $anchor): string {
        $params = array_filter(
            array_merge([
                'orders_q' => $filters['q'],
                'orders_filter' => $filters['filter'],
                'orders_period' => $filters['period'],
            ], $overrides),
            fn ($value) => $value !== null && $value !== '' && $value !== 'all',
        );

        return route('profile', $params) . $anchor;
    };

    $imageUrl = function (?\App\Models\Product $product): string {
        $path = $product?->images->first()?->path;

        if (! $path) {
            return asset('images/product-placeholder.jpg');
        }

        return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    };

    $orderBadge = fn (\App\Enums\OrderStatus $status): string => match ($status) {
        \App\Enums\OrderStatus::Paid => 'bg-success',
        \App\Enums\OrderStatus::Pending => 'bg-warning fg-body',
        \App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Expired => 'bg-secondary',
    };
@endphp

<section
    id="pane-purchases"
    class="tab-pane fade"
    role="tabpanel"
    aria-labelledby="tab-purchases"
    tabindex="0"
>
    <div class="card border-0 shadow-sm rounded-9 p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">{{ __('profile.purchases.title') }}</h2>
                <p class="fg-3 mb-0">{{ __('profile.purchases.intro') }}</p>
            </div>

            <form
                action="{{ route('profile') }}{{ $anchor }}"
                method="GET"
                role="search"
                class="d-flex gap-2 flex-grow-1 flex-lg-grow-0"
                style="min-width: min(100%, 22rem);"
            >
                @if ($filters['filter'] !== 'all')
                    <input type="hidden" name="orders_filter" value="{{ $filters['filter'] }}">
                @endif
                @if ($filters['period'] !== 'all')
                    <input type="hidden" name="orders_period" value="{{ $filters['period'] }}">
                @endif
                <label for="orders-search" class="visually-hidden">{{ __('profile.purchases.search_label') }}</label>
                <input
                    id="orders-search"
                    type="search"
                    name="orders_q"
                    value="{{ $filters['q'] }}"
                    maxlength="{{ \App\Queries\Orders\PurchaseHistoryQuery::MAX_KEYWORD_LENGTH }}"
                    class="form-control"
                    placeholder="{{ __('profile.purchases.search_placeholder') }}"
                >
                <button type="submit" class="btn btn-solid theme-primary px-3">
                    {{ __('profile.purchases.search') }}
                </button>
            </form>
        </div>

        <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="{{ __('profile.purchases.filters_label') }}">
            @foreach (\App\Queries\Orders\PurchaseHistoryQuery::FILTERS as $filterKey)
                @php($isActiveFilter = $filters['filter'] === $filterKey)
                <a
                    href="{{ $filterUrl(['orders_filter' => $filterKey]) }}"
                    @class([
                        'btn btn-sm rounded-pill px-3',
                        'btn-solid theme-primary' => $isActiveFilter,
                        'btn-outline theme-primary' => ! $isActiveFilter,
                    ])
                    @if ($isActiveFilter) aria-current="true" @endif
                >
                    {{ __('profile.purchases.filters.' . $filterKey) }}
                </a>
            @endforeach
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-2 mb-3">
            <h3 class="h5 mb-0">{{ __('profile.purchases.orders') }}</h3>

            <form action="{{ route('profile') }}{{ $anchor }}" method="GET" class="d-flex align-items-center gap-2">
                @if ($filters['q'] !== '')
                    <input type="hidden" name="orders_q" value="{{ $filters['q'] }}">
                @endif
                @if ($filters['filter'] !== 'all')
                    <input type="hidden" name="orders_filter" value="{{ $filters['filter'] }}">
                @endif
                <label for="orders-period" class="small fg-3 mb-0">{{ __('profile.purchases.period_label') }}</label>
                <select
                    id="orders-period"
                    name="orders_period"
                    class="form-select form-select-sm w-auto fw-semibold"
                    onchange="this.form.submit()"
                >
                    @foreach (array_keys(\App\Queries\Orders\PurchaseHistoryQuery::PERIODS) as $periodKey)
                        <option value="{{ $periodKey }}" @selected($filters['period'] === $periodKey)>
                            {{ __('profile.purchases.periods.' . $periodKey) }}
                        </option>
                    @endforeach
                </select>
                <noscript>
                    <button type="submit" class="btn btn-sm btn-outline theme-primary">
                        {{ __('profile.purchases.apply') }}
                    </button>
                </noscript>
            </form>
        </div>

        @forelse ($orders as $order)
            @php($isPaid = $order->status === \App\Enums\OrderStatus::Paid)
            @php($canPay = $order->status === \App\Enums\OrderStatus::Pending && (! $order->expires_at || $order->expires_at->isFuture()))

            <article class="border rounded-4 mb-3 overflow-hidden" aria-labelledby="purchase-order-{{ $order->getKey() }}">
                <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 px-3 py-2 bg-1 border-bottom small">
                    <div class="d-flex flex-wrap gap-4">
                        <div>
                            <div class="fg-3 text-uppercase">{{ __('profile.purchases.order_placed') }}</div>
                            <div class="fw-semibold">{{ $order->created_at->translatedFormat('M j, Y') }}</div>
                        </div>
                        <div>
                            <div class="fg-3 text-uppercase">{{ __('profile.purchases.total') }}</div>
                            <div class="fw-semibold">${{ number_format((float) $order->total_amount, 2) }}</div>
                        </div>
                        <div>
                            <div class="fg-3 text-uppercase">{{ __('profile.purchases.status') }}</div>
                            <span class="badge {{ $orderBadge($order->status) }}">
                                {{ __('checkout.order_status.' . $order->status->value) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-lg-end">
                        <div id="purchase-order-{{ $order->getKey() }}" class="fw-semibold">
                            {{ __('profile.purchases.order_number', ['id' => $order->getKey()]) }}
                        </div>
                        <a href="{{ route('orders.show', $order) }}" class="text-decoration-none">
                            {{ __('profile.purchases.view_details') }}
                        </a>
                    </div>
                </header>

                <ul class="list-unstyled mb-0">
                    @foreach ($order->details as $detail)
                        @php($product = $detail->product)
                        @php($productAvailable = $product && $product->status === \App\Models\Product::STATUS_APPROVED && $product->is_active)
                        <li @class(['d-flex flex-wrap gap-3 p-3', 'border-top' => ! $loop->first])>
                            <div class="flex-shrink-0 rounded-3 overflow-hidden border bg-white" style="width: 88px; height: 88px;">
                                <img
                                    src="{{ $imageUrl($product) }}"
                                    alt="{{ $product?->name ?? '' }}"
                                    class="w-100 h-100 object-fit-cover"
                                    loading="lazy"
                                >
                            </div>

                            <div class="flex-grow-1" style="min-width: 12rem;">
                                @if ($productAvailable)
                                    <a
                                        href="{{ route('products.show', ['product' => $product->getKey(), 'slug' => $product->slug]) }}"
                                        class="fw-semibold text-decoration-none fg-body"
                                    >{{ $product->name }}</a>
                                @elseif ($product)
                                    <span class="fw-semibold">{{ $product->name }}</span>
                                    <div class="small fg-3">{{ __('profile.purchases.unavailable_product') }}</div>
                                @else
                                    <span class="fw-semibold fg-3">{{ __('profile.purchases.unavailable_product') }}</span>
                                @endif

                                @if ($product?->vendor)
                                    <div class="small fg-3">
                                        {{ __('profile.purchases.sold_by', ['store' => $product->vendor->store_name]) }}
                                    </div>
                                @endif

                                <div class="small mt-1">
                                    {{ __('profile.purchases.quantity_price', [
                                        'count' => $detail->quantity,
                                        'price' => '$' . number_format((float) $detail->unit_price, 2),
                                    ]) }}
                                </div>

                                @if ($isPaid)
                                    <span class="badge bg-info mt-2">{{ $detail->fulfillment_status->label() }}</span>
                                @endif
                            </div>

                            <div class="d-flex flex-column gap-2 align-items-stretch" style="min-width: 10rem;">
                                @if ($productAvailable && $product->stock > 0)
                                    <a
                                        href="{{ route('products.show', ['product' => $product->getKey(), 'slug' => $product->slug]) }}"
                                        class="btn btn-sm btn-outline theme-primary"
                                    >{{ __('profile.purchases.buy_again') }}</a>
                                @endif

                                @if ($productAvailable && in_array($detail->product_id, $reviewableProductIds))
                                    <a
                                        href="{{ route('products.show', ['product' => $product->getKey(), 'slug' => $product->slug]) }}#product-reviews"
                                        class="btn btn-sm btn-solid theme-primary"
                                    >{{ __('profile.purchases.write_review') }}</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($canPay || $order->status === \App\Enums\OrderStatus::Pending)
                    <footer class="d-flex flex-wrap justify-content-end gap-2 px-3 py-2 border-top bg-1">
                        @if ($canPay)
                            <a href="{{ route('checkout.show', $order) }}" class="btn btn-sm btn-solid theme-primary">
                                {{ __('profile.purchases.complete_payment') }}
                            </a>
                        @endif
                        <form
                            action="{{ route('orders.cancel', $order) }}"
                            method="POST"
                            onsubmit="return confirm(@js(__('profile.purchases.cancel_confirm')))"
                        >
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline theme-danger">
                                {{ __('profile.purchases.cancel_order') }}
                            </button>
                        </form>
                    </footer>
                @endif
            </article>
        @empty
            <div class="text-center py-5">
                <i class="bi bi-bag fs-1 fg-3" aria-hidden="true"></i>
                <p class="fw-semibold mt-3 mb-1">{{ __('profile.purchases.empty') }}</p>
                @if ($hasActiveFilters)
                    <p class="fg-3">{{ __('profile.purchases.empty_filtered') }}</p>
                    <a href="{{ route('profile') }}{{ $anchor }}" class="btn btn-sm btn-outline theme-primary">
                        {{ __('profile.purchases.clear_filters') }}
                    </a>
                @else
                    <p class="fg-3">{{ __('profile.purchases.empty_intro') }}</p>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-solid theme-primary">
                        {{ __('profile.purchases.browse') }}
                    </a>
                @endif
            </div>
        @endforelse

        @if ($orders->hasPages())
            <nav class="mt-3" aria-label="{{ __('profile.purchases.pages') }}">
                {{ $orders->links('shared.pagination') }}
            </nav>
        @endif
    </div>
</section>
