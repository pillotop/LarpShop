@extends('layouts.app')

@section('title', __('management.sales_inventory_reports').' (UC-36) | ' . $vendor->store_name)

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
            :cart="$cart ?? null"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('management.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">{{ __('management.profile') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.store.settings') }}">{{ $vendor->store_name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('management.analytics_reports') }}</li>
            </ol>
        </nav>

        {{-- Header & Range Picker --}}
        <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h4 fw-bold fg-body mb-1">
                        <i class="bi bi-graph-up fg-primary me-2"></i>{{ __('management.sales_inventory_overview') }}
                    </h1>
                    <p class="fg-3 small mb-0">
                        {{ __('management.copy.reports_intro', ['store' => $vendor->store_name]) }}
                    </p>
                </div>
                <form method="GET" action="{{ route('vendor.reports.index') }}" class="d-flex align-items-center gap-2">
                    <label for="rangeSelect" class="form-label mb-0 fg-3 small fw-semibold">{{ __('management.period') }}</label>
                    <select id="rangeSelect" name="range" class="form-control form-control-sm rounded-pill px-3" onchange="this.form.submit()">
                        @foreach ($ranges as $value => $label)
                            <option value="{{ $value }}" @selected($range === $value)>{{ __('management.periods.'.$value) }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-solid theme-inverse rounded-pill">{{ __('management.filter') }}</button></noscript>
                </form>
            </div>
        </div>

        {{-- Financial Metrics Cards --}}
        <h2 class="h6 text-uppercase fw-bold fg-3 mb-3"><i class="bi bi-currency-dollar me-1"></i>{{ __('management.financial_overview') }}</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.gross_revenue') }}</div>
                    <div class="fs-lg fw-bold fg-success">${{ number_format($metrics['gross_revenue'], 2) }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.from_verified_paid_orders') }}</div>
                </div>
            </div>
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.orders_count') }}</div>
                    <div class="fs-lg fw-bold fg-body">{{ number_format($metrics['total_orders']) }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.orders_containing_your_products') }}</div>
                </div>
            </div>
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.units_sold') }}</div>
                    <div class="fs-lg fw-bold fg-primary">{{ number_format($metrics['units_sold']) }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.total_items_purchased') }}</div>
                </div>
            </div>
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.avg_order_value') }}</div>
                    <div class="fs-lg fw-bold fg-body">${{ number_format($metrics['average_order_value'], 2) }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.per_vendor_order') }}</div>
                </div>
            </div>
        </div>

        {{-- Inventory Health Cards --}}
        <h2 class="h6 text-uppercase fw-bold fg-3 mb-3"><i class="bi bi-boxes me-1"></i>{{ __('management.catalog_inventory_health') }}</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.total_catalog') }}</div>
                    <div class="fs-lg fw-bold fg-body">{{ $metrics['total_products'] }}</div>
                    <div class="fg-3 small mt-1">{{ $metrics['approved_count'] }} {{ __('management.quantity_labels.approved') }}</div>
                </div>
            </div>
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.in_stock') }}</div>
                    <div class="fs-lg fw-bold fg-success">{{ $metrics['in_stock_count'] }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.healthy_inventory_levels') }}</div>
                </div>
            </div>
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.low_stock_warning') }}</div>
                    <div class="fs-lg fw-bold fg-warning">{{ $metrics['low_stock_count'] }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.5_units_remaining') }}</div>
                </div>
            </div>
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">{{ __('management.out_of_stock') }}</div>
                    <div class="fs-lg fw-bold fg-danger">{{ $metrics['out_of_stock_count'] }}</div>
                    <div class="fg-3 small mt-1">{{ __('management.needs_immediate_restocking') }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Top Products Table --}}
            <div class="col-12 lg:col-7">
                <div class="card border-0 shadow-sm rounded-9 bg-white h-100 overflow-hidden">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h5 fw-bold mb-0"><i class="bi bi-trophy fg-warning me-2"></i>{{ __('management.top_selling_products') }}</h2>
                        <span class="badge bg-1 fg-body border">{{ __('management.top_5_by_revenue') }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-1">
                                <tr>
                                    <th class="ps-4">{{ __('management.product') }}</th>
                                    <th>{{ __('management.units_sold') }}</th>
                                    <th>{{ __('management.revenue') }}</th>
                                    <th class="pe-4 text-end">{{ __('management.stock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($top_products as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold fg-body">{{ $item->name }}</div>
                                            <span class="fg-3 small">{{ __('management.display.id') }}{{ $item->product_id }}</span>
                                        </td>
                                        <td><span class="badge bg-subtle-primary fg-primary">{{ $item->units_sold }} {{ __('management.quantity_labels.pcs') }}</span></td>
                                        <td class="fw-bold fg-success">${{ number_format($item->revenue, 2) }}</td>
                                        <td class="pe-4 text-end">
                                            @if ($item->stock <= 0)
                                                <span class="badge bg-danger">0</span>
                                            @elseif ($item->stock <= 5)
                                                <span class="badge bg-warning fg-body">{{ $item->stock }}</span>
                                            @else
                                                <span class="badge bg-1 fg-body border">{{ $item->stock }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 fg-3">
                                            {{ __('management.no_sales_recorded_in_this_time_range') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Low Stock Urgent Alert Table --}}
            <div class="col-12 lg:col-5">
                <div class="card border-0 shadow-sm rounded-9 bg-white h-100 overflow-hidden">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h5 fw-bold mb-0 fg-danger"><i class="bi bi-exclamation-triangle-fill fg-danger me-2"></i>{{ __('management.low_stock_alerts') }}</h2>
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-sm btn-outline theme-secondary rounded-pill px-2">{{ __('management.manage_stock') }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-1">
                                <tr>
                                    <th class="ps-4">{{ __('management.product') }}</th>
                                    <th>{{ __('management.price') }}</th>
                                    <th class="pe-4 text-end">{{ __('management.stock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($low_stock_products as $product)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold fg-body">{{ $product->name }}</div>
                                            <span class="fg-3 small">{{ __('management.display.status') }} {{ __('products.vendor_status.'.$product->status) }}</span>
                                        </td>
                                        <td class="fg-3 small">${{ number_format($product->price, 2) }}</td>
                                        <td class="pe-4 text-end">
                                            @if ($product->stock == 0)
                                                <span class="badge bg-danger">{{ __('management.out_of_stock') }}</span>
                                            @else
                                                <span class="badge bg-warning fg-body">{{ $product->stock }} {{ __('management.quantity_labels.left') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 fg-success">
                                            <i class="bi bi-check-circle me-1"></i>{{ __('management.all_products_have_sufficient_stock') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
