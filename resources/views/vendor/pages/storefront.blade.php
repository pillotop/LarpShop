{{-- resources/views/vendor/pages/storefront.blade.php --}}
{{-- Vendor storefront page for UC-07 --}}

@extends('layouts.app')

@section('title', $vendor->store_name . ' | LarpShop')

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/product_card.css">
    <link rel="stylesheet" href="/css/components/rating_container.css">
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
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            {{ Breadcrumbs::render('vendor', $vendor) }}
        </nav>

        {{-- Vendor Store Header / Profile Banner --}}
        <section class="card border-0 shadow-sm p-4 mb-4 rounded-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #eef2f5 100%);">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow" style="width: 72px; height: 72px; font-size: 2rem;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h3 fw-bold mb-0 text-dark">{{ $vendor->store_name }}</h1>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                            <i class="bi bi-patch-check-fill me-1"></i>Verified Merchant
                        </span>
                    </div>

                    @if ($vendor->description)
                        <p class="text-secondary mt-2 mb-2">{{ $vendor->description }}</p>
                    @endif

                    <div class="d-flex align-items-center gap-4 text-muted small mt-2">
                        <span>
                            <i class="bi bi-calendar3 me-1"></i>Joined {{ $vendor->created_at ? $vendor->created_at->format('M Y') : 'recently' }}
                        </span>
                        <span>
                            <i class="bi bi-box-seam me-1"></i><strong>{{ $products->total() }}</strong> Products available
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Filter & Search Toolbar --}}
        <section class="mb-4">
            <div class="card border-0 shadow-sm p-3 rounded-3">
                <form method="GET" action="{{ route('vendors.show', ['vendor' => $vendor->vendor_id]) }}" class="row g-2 align-items-center">
                    {{-- Search within store --}}
                    <div class="col-12 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                class="form-control bg-light border-start-0"
                                placeholder="Search in this store..."
                                aria-label="Search products"
                            >
                        </div>
                    </div>

                    {{-- Category filter --}}
                    <div class="col-6 col-md-3">
                        <select name="category_id" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            @foreach ($vendorCategories as $cat)
                                <option value="{{ $cat->category_id }}" {{ (string)request('category_id') === (string)$cat->category_id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort options --}}
                    <div class="col-6 col-md-3">
                        <select name="sort" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Newest Arrivals</option>
                            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>

                {{-- Active filter indicators --}}
                @if (request('q') || request('category_id') || request('sort'))
                    <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top small">
                        <span class="text-muted">Filtering by:</span>
                        @if (request('q'))
                            <span class="badge bg-secondary">Keyword: "{{ request('q') }}"</span>
                        @endif
                        @if (request('category_id'))
                            @php $activeCategory = $vendorCategories->firstWhere('category_id', request('category_id')); @endphp
                            @if ($activeCategory)
                                <span class="badge bg-secondary">Category: {{ $activeCategory->name }}</span>
                            @endif
                        @endif
                        <a href="{{ route('vendors.show', ['vendor' => $vendor->vendor_id]) }}" class="text-danger text-decoration-none ms-auto">
                            <i class="bi bi-x-circle me-1"></i>Reset filters
                        </a>
                    </div>
                @endif
            </div>
        </section>

        {{-- Products Grid --}}
        <section class="mb-5">
            @if ($products->count() > 0)
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    @foreach ($products as $product)
                        <div class="col">
                            <x-product-card-b :product="$product" />
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-center mt-5">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5 my-4 bg-light rounded-4">
                    <div class="text-muted mb-3" style="font-size: 3rem;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h2 class="h5 fw-bold text-dark">No products found</h2>
                    <p class="text-muted small mb-3">
                        @if (request('q') || request('category_id'))
                            Try adjusting your search or filters to find what you're looking for.
                        @else
                            This vendor currently has no products listed.
                        @endif
                    </p>
                    @if (request('q') || request('category_id'))
                        <a href="{{ route('vendors.show', ['vendor' => $vendor->vendor_id]) }}" class="btn btn-outline-primary btn-sm">
                            Clear filters
                        </a>
                    @endif
                </div>
            @endif
        </section>
    </main>

    <x-side-panel/>
    <x-modal/>
    <x-company-footer/>
@endsection
