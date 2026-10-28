@extends('layouts.app')

@section('title', 'Sales & Inventory Reports (UC-36) | ' . $vendor->store_name)

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        <link rel="stylesheet" href="/css/top_header.css">
        <link rel="stylesheet" href="/css/components/navigation_bar.css">
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
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
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">
                        <i class="bi bi-graph-up text-primary me-2"></i>Sales & Inventory Overview
                    </h1>
                    <p class="text-muted small mb-0">
                        Performance analytics for <strong>{{ $vendor->store_name }}</strong>. Revenue includes verified paid orders.
                    </p>
                </div>
                <form method="GET" action="{{ route('vendor.reports.index') }}" class="d-flex align-items-center gap-2">
                    <label for="rangeSelect" class="form-label mb-0 text-muted small fw-semibold">Period:</label>
                    <select id="rangeSelect" name="range" class="form-select form-select-sm rounded-pill px-3" onchange="this.form.submit()">
                        @foreach ($ranges as $value => $label)
                            <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-dark rounded-pill">Filter</button></noscript>
                </form>
            </div>
        </div>

        {{-- Financial Metrics Cards --}}
        <h2 class="h6 text-uppercase fw-bold text-muted mb-3"><i class="bi bi-currency-dollar me-1"></i>Financial Overview</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Gross Revenue</div>
                    <div class="fs-4 fw-bold text-success">${{ number_format($metrics['gross_revenue'], 2) }}</div>
                    <div class="text-muted small mt-1">From verified paid orders</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Orders Count</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($metrics['total_orders']) }}</div>
                    <div class="text-muted small mt-1">Orders containing your products</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Units Sold</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($metrics['units_sold']) }}</div>
                    <div class="text-muted small mt-1">Total items purchased</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Avg Order Value</div>
                    <div class="fs-4 fw-bold text-dark">${{ number_format($metrics['average_order_value'], 2) }}</div>
                    <div class="text-muted small mt-1">Per vendor order</div>
                </div>
            </div>
        </div>

        {{-- Inventory Health Cards --}}
        <h2 class="h6 text-uppercase fw-bold text-muted mb-3"><i class="bi bi-boxes me-1"></i>Catalog & Inventory Health</h2>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Total Catalog</div>
                    <div class="fs-4 fw-bold text-dark">{{ $metrics['total_products'] }}</div>
                    <div class="text-muted small mt-1">{{ $metrics['approved_count'] }} approved</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">In Stock</div>
                    <div class="fs-4 fw-bold text-success">{{ $metrics['in_stock_count'] }}</div>
                    <div class="text-muted small mt-1">Healthy inventory levels</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Low Stock Warning</div>
                    <div class="fs-4 fw-bold text-warning">{{ $metrics['low_stock_count'] }}</div>
                    <div class="text-muted small mt-1">&le; 5 units remaining</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small mb-1">Out of Stock</div>
                    <div class="fs-4 fw-bold text-danger">{{ $metrics['out_of_stock_count'] }}</div>
                    <div class="text-muted small mt-1">Needs immediate restocking</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Top Products Table --}}
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h5 fw-bold mb-0"><i class="bi bi-trophy text-warning me-2"></i>Top Selling Products</h2>
                        <span class="badge bg-light text-dark border">Top 5 by Revenue</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
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
                                            <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                            <span class="text-muted small">ID: #{{ $item->product_id }}</span>
                                        </td>
                                        <td><span class="badge bg-primary-subtle text-primary">{{ $item->units_sold }} pcs</span></td>
                                        <td class="fw-bold text-success">${{ number_format($item->revenue, 2) }}</td>
                                        <td class="pe-4 text-end">
                                            @if ($item->stock <= 0)
                                                <span class="badge bg-danger">0</span>
                                            @elseif ($item->stock <= 5)
                                                <span class="badge bg-warning text-dark">{{ $item->stock }}</span>
                                            @else
                                                <span class="badge bg-light text-dark border">{{ $item->stock }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
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
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h5 fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Low Stock Alerts</h2>
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Manage Stock</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
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
                                            <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                            <span class="text-muted small">Status: {{ ucfirst($product->status) }}</span>
                                        </td>
                                        <td class="text-muted small">${{ number_format($product->price, 2) }}</td>
                                        <td class="pe-4 text-end">
                                            @if ($product->stock == 0)
                                                <span class="badge bg-danger">Out of stock</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ $product->stock }} left</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-success">
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
