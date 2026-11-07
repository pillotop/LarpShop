{{-- resources/views/vendor/pages/storefront.blade.php --}}
{{-- Vendor storefront page for UC-07 --}}

@extends('layouts.app')

@section('title', $vendor->store_name . ' | LarpShop')

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/product_card.css',
        'resources/css/components/rating_container.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/product_quantity_picker.js',
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
        <x-persistent-header
            :vendor="$vendor"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            {{ Breadcrumbs::render('vendor', $vendor) }}
        </nav>

        {{-- Vendor Store Header / Profile Banner --}}
        <section class="card border-0 shadow-sm p-4 mb-4 rounded-9" style="background: linear-gradient(135deg, #f8f9fa 0%, #eef2f5 100%);">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="rounded-circle bg-primary fg-white d-flex align-items-center justify-content-center shadow" style="width: 72px; height: 72px; font-size: 2rem;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h3 fw-bold mb-0 fg-body">{{ $vendor->store_name }}</h1>
                        <span class="badge bg-subtle-success fg-success border border-success rounded-pill px-3 py-1">
                            <i class="bi bi-patch-check-fill me-1"></i>{{ __('management.verified_merchant') }}
                        </span>
                    </div>

                    @if ($vendor->description)
                        <p class="fg-secondary mt-2 mb-2">{{ $vendor->description }}</p>
                    @endif

                    <div class="d-flex align-items-center gap-4 fg-3 small mt-2">
                        <span>
                            <i class="bi bi-calendar3 me-1"></i>{{ __('management.joined', ['date' => $vendor->created_at ? $vendor->created_at->locale(app()->getLocale())->translatedFormat('M Y') : __('management.recently')]) }}
                        </span>
                        <span>
                            <i class="bi bi-box-seam me-1"></i><strong>{{ $products->total() }}</strong> {{ __('management.products_available') }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Filter & Search Toolbar --}}
        <section class="mb-4">
            <div class="card border-0 shadow-sm p-3 rounded-5">
                <form method="GET" action="{{ route('vendors.show', ['vendor' => $vendor->vendor_id]) }}" class="row g-2 align-items-center">
                    {{-- Search within store --}}
                    <div class="col-12 md:col-5">
                        <div class="input-group">
                            <span class="input-group-text bg-1 border-end-0">
                                <i class="bi bi-search fg-3"></i>
                            </span>
                            <input
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                class="form-control bg-1 border-start-0"
                                placeholder="{{ __('management.search_in_this_store') }}"
                                aria-label="{{ __('management.search_products') }}"
                            >
                        </div>
                    </div>

                    {{-- Category filter --}}
                    <div class="col-6 md:col-3">
                        <select name="category_id" class="form-control bg-1" onchange="this.form.submit()">
                            <option value="">{{ __('management.all_categories') }}</option>
                            @foreach ($vendorCategories as $cat)
                                <option value="{{ $cat->category_id }}" {{ (string)request('category_id') === (string)$cat->category_id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort options --}}
                    <div class="col-6 md:col-3">
                        <select name="sort" class="form-control bg-1" onchange="this.form.submit()">
                            <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>{{ __('management.newest_arrivals') }}</option>
                            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>{{ __('management.price_low_to_high') }}</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>{{ __('management.price_high_to_low') }}</option>
                            <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>{{ __('management.name_a_to_z') }}</option>
                        </select>
                    </div>

                    <div class="col-12 md:col-1 d-grid">
                        <button type="submit" class="btn btn-solid theme-primary">{{ __('management.filter') }}</button>
                    </div>
                </form>

                {{-- Active filter indicators --}}
                @if (request('q') || request('category_id') || request('sort'))
                    <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top small">
                        <span class="fg-3">{{ __('management.filtering_by') }}</span>
                        @if (request('q'))
                            <span class="badge bg-secondary">{{ __('management.keyword', ['keyword' => request('q')]) }}</span>
                        @endif
                        @if (request('category_id'))
                            @php $activeCategory = $vendorCategories->firstWhere('category_id', request('category_id')); @endphp
                            @if ($activeCategory)
                                <span class="badge bg-secondary">{{ __('management.display.category') }} {{ $activeCategory->name }}</span>
                            @endif
                        @endif
                        <a href="{{ route('vendors.show', ['vendor' => $vendor->vendor_id]) }}" class="fg-danger text-decoration-none ms-auto">
                            <i class="bi bi-x-circle me-1"></i>{{ __('management.reset_filters') }}
                        </a>
                    </div>
                @endif
            </div>
        </section>

        {{-- Products Grid --}}
        <section class="mb-5">
            @if ($products->count() > 0)
                <div class="row row-cols-1 sm:row-cols-2 md:row-cols-3 lg:row-cols-4 g-4">
                    @foreach ($products as $product)
                        <div class="col">
                            <x-product-card-b :product="$product" />
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-center mt-5">
                    {{ $products->links('shared.pagination') }}
                </div>
            @else
                <div class="text-center py-5 my-4 bg-1 rounded-9">
                    <div class="fg-3 mb-3" style="font-size: 3rem;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h2 class="h5 fw-bold fg-body">{{ __('management.no_products_found') }}</h2>
                    <p class="fg-3 small mb-3">
                        @if (request('q') || request('category_id'))
                            {{ __('management.storefront.try_filters') }}
                        @else
                            {{ __('management.storefront.empty') }}
                        @endif
                    </p>
                    @if (request('q') || request('category_id'))
                        <a href="{{ route('vendors.show', ['vendor' => $vendor->vendor_id]) }}" class="btn btn-outline theme-primary btn-sm">
                            {{ __('management.clear_filters') }}
                        </a>
                    @endif
                </div>
            @endif
        </section>
    </main>

    <x-locale-panels :vendor="$vendor" />
    <x-modal/>
    <x-company-footer/>
@endsection
