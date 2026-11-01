@extends('layouts.app')

@section('title', 'Sales & Inventory Reports (UC-36) | ' . $vendor->store_name)

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
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">Profile</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.store.settings') }}">{{ $vendor->store_name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Analytics & Reports</li>
            </ol>
        </nav>

        {{-- Header & Range Picker --}}
        <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h4 fw-bold fg-body mb-1">
                        <i class="bi bi-graph-up fg-primary me-2"></i>Sales & Inventory Overview
                    </h1>
                    <p class="fg-3 small mb-0">
                        Performance analytics for <strong>{{ $vendor->store_name }}</strong>. Revenue includes verified paid orders.
                    </p>
                </div>
                <form method="GET" action="{{ route('vendor.reports.index') }}" class="d-flex align-items-center gap-2">
                    <label for="rangeSelect" class="form-label mb-0 fg-3 small fw-semibold">Period:</label>
                    <select id="rangeSelect" name="range" class="form-control form-control-sm rounded-pill px-3" onchange="this.form.submit()">
                        @foreach ($ranges as $value => $label)
                            <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-solid theme-inverse rounded-pill">Filter</button></noscript>
                </form>
            </div>
        </div>

        {{-- Financial Metrics Cards --}}
        <h2 class="h6 text-uppercase fw-bold fg-3 mb-3"><i class="bi bi-currency-dollar me-1"></i>Financial Overview</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Gross Revenue</div>
                    <div class="fs-lg fw-bold fg-success">${{ number_format($metrics['gross_revenue'], 2) }}</div>
                    <div class="fg-3 small mt-1">From verified paid orders</div>
                </div>
            </div>
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Orders Count</div>
                    <div class="fs-lg fw-bold fg-body">{{ number_format($metrics['total_orders']) }}</div>
                    <div class="fg-3 small mt-1">Orders containing your products</div>
                </div>
            </div>
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Units Sold</div>
                    <div class="fs-lg fw-bold fg-primary">{{ number_format($metrics['units_sold']) }}</div>
                    <div class="fg-3 small mt-1">Total items purchased</div>
                </div>
            </div>
            <div class="col-12 sm:col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Avg Order Value</div>
                    <div class="fs-lg fw-bold fg-body">${{ number_format($metrics['average_order_value'], 2) }}</div>
                    <div class="fg-3 small mt-1">Per vendor order</div>
                </div>
            </div>
        </div>

        {{-- Inventory Health Cards --}}
        <h2 class="h6 text-uppercase fw-bold fg-3 mb-3"><i class="bi bi-boxes me-1"></i>Catalog & Inventory Health</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Total Catalog</div>
                    <div class="fs-lg fw-bold fg-body">{{ $metrics['total_products'] }}</div>
                    <div class="fg-3 small mt-1">{{ $metrics['approved_count'] }} approved</div>
                </div>
            </div>
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">In Stock</div>
                    <div class="fs-lg fw-bold fg-success">{{ $metrics['in_stock_count'] }}</div>
                    <div class="fg-3 small mt-1">Healthy inventory levels</div>
                </div>
            </div>
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Low Stock Warning</div>
                    <div class="fs-lg fw-bold fg-warning">{{ $metrics['low_stock_count'] }}</div>
                    <div class="fg-3 small mt-1">&le; 5 units remaining</div>
                </div>
            </div>
            <div class="col-6 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small mb-1">Out of Stock</div>
                    <div class="fs-lg fw-bold fg-danger">{{ $metrics['out_of_stock_count'] }}</div>
                    <div class="fg-3 small mt-1">Needs immediate restocking</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Top Products Table --}}
            <div class="col-12 lg:col-7">
                <div class="card border-0 shadow-sm rounded-9 bg-white h-100 overflow-hidden">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h5 fw-bold mb-0"><i class="bi bi-trophy fg-warning me-2"></i>Top Selling Products</h2>
                        <span class="badge bg-1 fg-body border">Top 5 by Revenue</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-1">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th class="pe-4 text-end">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($top_products as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold fg-body">{{ $item->name }}</div>
                                            <span class="fg-3 small">ID: #{{ $item->product_id }}</span>
                                        </td>
                                        <td><span class="badge bg-subtle-primary fg-primary">{{ $item->units_sold }} pcs</span></td>
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
                                            No sales recorded in this time range.
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
                        <h2 class="h5 fw-bold mb-0 fg-danger"><i class="bi bi-exclamation-triangle-fill fg-danger me-2"></i>Low Stock Alerts</h2>
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-sm btn-outline theme-secondary rounded-pill px-2">Manage Stock</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-1">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th>Price</th>
                                    <th class="pe-4 text-end">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($low_stock_products as $product)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold fg-body">{{ $product->name }}</div>
                                            <span class="fg-3 small">Status: {{ ucfirst($product->status) }}</span>
                                        </td>
                                        <td class="fg-3 small">${{ number_format($product->price, 2) }}</td>
                                        <td class="pe-4 text-end">
                                            @if ($product->stock == 0)
                                                <span class="badge bg-danger">Out of stock</span>
                                            @else
                                                <span class="badge bg-warning fg-body">{{ $product->stock }} left</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 fg-success">
                                            <i class="bi bi-check-circle me-1"></i>All products have sufficient stock.
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
