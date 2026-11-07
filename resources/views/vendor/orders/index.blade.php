@extends('layouts.app')

@section('title', __('management.store_orders').' (UC-34) | ' . $vendor->store_name)

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
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

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

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('management.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">{{ __('management.account_profile') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('management.store_orders') }}</li>
            </ol>
        </nav>

        @if (session('success'))
            <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('management.close') }}"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-9 p-3 mb-4 shadow-sm" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('management.close') }}"></button>
            </div>
        @endif

        {{-- Fulfillment Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.total_store_orders') }}</div>
                    <div class="fs-lg fw-bold fg-body mt-1">{{ $summary['total_orders'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.paid_orders') }}</div>
                    <div class="fs-lg fw-bold fg-success mt-1">{{ $summary['paid_orders'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.unfulfilled_items') }}</div>
                    <div class="fs-lg fw-bold fg-emphasis-warning mt-1">{{ $summary['unfulfilled'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.processing') }}</div>
                    <div class="fs-lg fw-bold fg-primary mt-1">{{ $summary['processing'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.shipped') }}</div>
                    <div class="fs-lg fw-bold fg-emphasis-info mt-1">{{ $summary['shipped'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.delivered') }}</div>
                    <div class="fs-lg fw-bold fg-success mt-1">{{ $summary['delivered'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                <div>
                    <h3 class="fw-bold fg-body mb-1">
                        <i class="bi bi-truck fg-primary me-2"></i>{{ __('management.store_orders_fulfillment_uc_34_uc_35') }}
                    </h3>
                    <p class="fg-3 small mb-0">
                        {{ __('management.copy.orders_intro', ['store' => $vendor->store_name]) }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.products.index') }}" class="btn btn-outline theme-primary rounded-pill px-3">
                        <i class="bi bi-box-seam me-1"></i>{{ __('management.store_products') }}
                    </a>
                    <a href="{{ route('vendor.store.settings') }}" class="btn btn-outline theme-secondary rounded-pill px-3">
                        <i class="bi bi-gear me-1"></i>{{ __('management.store_settings') }}
                    </a>
                </div>
            </div>

            {{-- Filter & Search Bar --}}
            <form action="{{ route('vendor.orders.index') }}" method="GET" class="row g-2 align-items-end mb-4">
                <div class="md:col-4">
                    <label for="filter-q" class="form-label small fg-3 fw-semibold mb-1">{{ __('management.search_order_or_customer') }}</label>
                    <input
                        type="search"
                        id="filter-q"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        class="form-control rounded-pill"
                        placeholder="{{ __('management.search_by_order_id_username_or_email') }}"
                        maxlength="100"
                    >
                </div>
                <div class="md:col-3">
                    <label for="filter-order-status" class="form-label small fg-3 fw-semibold mb-1">{{ __('management.order_payment_status') }}</label>
                    <select id="filter-order-status" name="order_status" class="form-control rounded-pill">
                        <option value="all">{{ __('management.all_order_statuses') }}</option>
                        @foreach ($orderStatuses as $statusCase)
                            <option value="{{ $statusCase->value }}" @selected(($filters['order_status'] ?? 'all') === $statusCase->value)>
                                {{ __('checkout.order_status.'.$statusCase->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-3">
                    <label for="filter-fulfillment-status" class="form-label small fg-3 fw-semibold mb-1">{{ __('management.item_fulfillment_status') }}</label>
                    <select id="filter-fulfillment-status" name="fulfillment_status" class="form-control rounded-pill">
                        <option value="all">{{ __('management.all_fulfillment_states') }}</option>
                        @foreach ($fulfillmentStatuses as $fStatus)
                            <option value="{{ $fStatus->value }}" @selected(($filters['fulfillment_status'] ?? 'all') === $fStatus->value)>
                                {{ $fStatus->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-2 d-flex gap-2">
                    <button type="submit" class="btn btn-solid theme-inverse rounded-pill w-100">{{ __('management.filter') }}</button>
                    @if (! empty($filters['q']) || ($filters['order_status'] ?? 'all') !== 'all' || ($filters['fulfillment_status'] ?? 'all') !== 'all')
                        <a href="{{ route('vendor.orders.index') }}" class="btn btn-outline theme-secondary rounded-pill">{{ __('management.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col">{{ __('management.order') }}</th>
                            <th scope="col">{{ __('management.customer') }}</th>
                            <th scope="col">{{ __('management.payment_status') }}</th>
                            <th scope="col">{{ __('management.your_store_items') }}</th>
                            <th scope="col">{{ __('management.your_store_subtotal') }}</th>
                            <th scope="col">{{ __('management.fulfillment_status') }}</th>
                            <th scope="col" class="text-end">{{ __('management.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $orderStatusValue = $order->status instanceof \App\Enums\OrderStatus ? $order->status->value : (string) $order->status;
                                $vendorItemCount = $order->details->sum('quantity');
                                $distinctStatuses = $order->details
                                    ->map(fn ($d) => $d->fulfillment_status instanceof \App\Enums\FulfillmentStatus ? $d->fulfillment_status : \App\Enums\FulfillmentStatus::from((string) $d->fulfillment_status))
                                    ->unique();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold fg-body">#{{ $order->order_id }}</div>
                                    <small class="fg-3">{{ $order->created_at?->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold fg-body">{{ $order->user?->username ?? __('management.guest') }}</div>
                                    <small class="fg-3">{{ $order->user?->email }}</small>
                                </td>
                                <td>
                                    <span @class([
                                        'badge rounded-pill px-3 py-1',
                                        'bg-subtle-success fg-success' => $orderStatusValue === 'paid',
                                        'bg-subtle-warning fg-emphasis-warning' => $orderStatusValue === 'pending',
                                        'bg-subtle-secondary fg-secondary' => in_array($orderStatusValue, ['cancelled', 'expired'], true),
                                    ])>
                                        {{ __('checkout.order_status.'.$orderStatusValue) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $order->details->count() }} line(s) ({{ $vendorItemCount }} units)</div>
                                    <small class="fg-3 text-truncate d-inline-block" style="max-width: 240px;">
                                        {{ $order->details->pluck('product.name')->filter()->implode(', ') }}
                                    </small>
                                </td>
                                <td class="fw-bold fg-success">
                                    ${{ $subtotals[$order->order_id] ?? '0.00' }}
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($distinctStatuses as $fCase)
                                            <span @class([
                                                'badge rounded-pill px-2 py-1',
                                                'bg-subtle-warning fg-emphasis-warning' => $fCase->value === 'unfulfilled',
                                                'bg-subtle-primary fg-primary' => $fCase->value === 'processing',
                                                'bg-subtle-info fg-emphasis-info' => $fCase->value === 'shipped',
                                                'bg-subtle-success fg-success' => $fCase->value === 'delivered',
                                            ])>
                                                {{ $fCase->label() }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('vendor.orders.show', $order->order_id) }}" class="btn btn-sm btn-solid theme-primary rounded-pill px-3 fw-semibold">
                                        {{ __('management.manage_order') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 fg-3">
                                    <i class="bi bi-inbox fs-3xl d-block mb-3 fg-secondary"></i>
                                    {{ __('management.no_orders_containing_products_from_this_store_were_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="mt-4 pt-3 border-top">
                    {{ $orders->links('shared.pagination') }}
                </div>
            @endif
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
