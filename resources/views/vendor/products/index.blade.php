@extends('layouts.app')

@section('title', 'Products & Inventory | ' . $vendor->store_name)

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
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">Account Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Products & Inventory</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-4 p-3 mb-4 shadow-sm" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Inventory & Visibility Summary Cards (UC-33) --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small fw-semibold">Total Products</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $summary['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small fw-semibold">Visible</div>
                    <div class="fs-4 fw-bold text-success mt-1">{{ $summary['visible'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small fw-semibold">Hidden</div>
                    <div class="fs-4 fw-bold text-secondary mt-1">{{ $summary['hidden'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small fw-semibold">In Stock</div>
                    <div class="fs-4 fw-bold text-primary mt-1">{{ $summary['in_stock'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small fw-semibold">Low Stock (&le; 5)</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1">{{ $summary['low_stock'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                    <div class="text-muted small fw-semibold">Out of Stock</div>
                    <div class="fs-4 fw-bold text-danger mt-1">{{ $summary['out_of_stock'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-box-seam text-primary me-2"></i>Store Products & Inventory
                    </h3>
                    <p class="text-muted small mb-0">
                        Create listings, update stock levels, toggle catalog visibility, and manage images for <strong>{{ $vendor->store_name }}</strong>.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('vendor.products.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>New Product Listing
                    </a>
                </div>
            </div>

            {{-- Filter & Search Bar --}}
            <form action="{{ route('vendor.products.index') }}" method="GET" class="row g-2 align-items-end mb-4">
                <div class="col-md-4">
                    <label for="filter-q" class="form-label small text-muted fw-semibold mb-1">Search</label>
                    <input
                        type="search"
                        id="filter-q"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        class="form-control rounded-pill"
                        placeholder="Search by product name or slug..."
                        maxlength="100"
                    >
                </div>
                <div class="col-md-3">
                    <label for="filter-category" class="form-label small text-muted fw-semibold mb-1">Category</label>
                    <select id="filter-category" name="category_id" class="form-select rounded-pill">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->category_id }}" @selected(($filters['category_id'] ?? null) === (int) $cat->category_id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-availability" class="form-label small text-muted fw-semibold mb-1">Availability & Stock</label>
                    <select id="filter-availability" name="availability" class="form-select rounded-pill">
                        @foreach ($availabilityFilters as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['availability'] ?? 'all') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark rounded-pill w-100">Filter</button>
                    @if (! empty($filters['q']) || ! empty($filters['category_id']) || ($filters['availability'] ?? 'all') !== 'all')
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-outline-secondary rounded-pill" title="Clear filters">Reset</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 72px;">Image</th>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col" style="width: 190px;">Inventory (UC-33)</th>
                            <th scope="col">Visibility</th>
                            <th scope="col" class="text-end" style="width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $primaryImage = $product->images->first();
                            @endphp
                            <tr class="{{ ! $product->is_active ? 'table-light opacity-75' : '' }}">
                                <td>
                                    <div class="ratio ratio-1x1 rounded-3 overflow-hidden bg-light" style="width: 56px;">
                                        @if ($primaryImage)
                                            <img src="{{ $primaryImage->url }}" class="object-fit-cover w-100 h-100" alt="{{ $product->name }}">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center text-muted">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                    <small class="text-muted">
                                        #{{ $product->product_id }} &bull; {{ $product->images->count() }} image(s)
                                        @if ($product->status !== 'approved')
                                            &bull; <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">{{ ucfirst($product->status) }}</span>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $product->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-success">
                                    ${{ number_format((float) $product->price, 2) }}
                                </td>
                                <td>
                                    {{-- UC-33: Quick inline inventory stock update --}}
                                    <form action="{{ route('vendor.products.update-stock', $product->product_id) }}" method="POST" class="d-flex align-items-center gap-1 m-0">
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            type="number"
                                            name="stock"
                                            min="0"
                                            max="1000000"
                                            value="{{ $product->stock }}"
                                            class="form-control form-control-sm rounded-pill text-center"
                                            style="width: 85px;"
                                            aria-label="Stock for {{ $product->name }}"
                                        >
                                        <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-2" title="Save stock">
                                            Save
                                        </button>
                                    </form>
                                    <div class="mt-1">
                                        @if ($product->stock === 0)
                                            <span class="badge bg-danger-subtle text-danger rounded-pill">Out of stock</span>
                                        @elseif ($product->stock <= 5)
                                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Low stock</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success rounded-pill">In stock</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($product->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                            <i class="bi bi-eye me-1"></i>Visible
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1">
                                            <i class="bi bi-eye-slash me-1"></i>Hidden
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                        {{-- UC-31: Edit Product --}}
                                        <a href="{{ route('vendor.products.edit', $product->product_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Edit product listing">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>

                                        {{-- UC-31 & UC-33: Hide / Show Product --}}
                                        <form action="{{ route('vendor.products.toggle-active', $product->product_id) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            @if ($product->is_active)
                                                <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3" title="Hide product from public catalog">
                                                    <i class="bi bi-eye-slash me-1"></i>Hide
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Show product in public catalog">
                                                    <i class="bi bi-eye me-1"></i>Show
                                                </button>
                                            @endif
                                        </form>

                                        {{-- UC-32: Manage Images --}}
                                        <a href="{{ route('vendor.products.images.index', $product->product_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Manage product images">
                                            <i class="bi bi-images me-1"></i>Images
                                        </a>

                                        @if ($product->is_active && $product->status === 'approved')
                                            <a href="{{ route('products.show', [$product->product_id, $product->slug]) }}" target="_blank" class="btn btn-sm btn-light rounded-pill px-2" title="View on storefront">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-box2 fs-1 d-block mb-3 text-secondary"></i>
                                    No products found for this store. Click <strong>New Product Listing</strong> to add your first item.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="mt-4 pt-3 border-top">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
