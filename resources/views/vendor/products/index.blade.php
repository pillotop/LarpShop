@extends('layouts.app')

@section('title', __('management.products_inventory').' | ' . $vendor->store_name)

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
                <li class="breadcrumb-item active" aria-current="page">{{ __('management.products_inventory') }}</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

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

        {{-- Inventory & Visibility Summary Cards (UC-33) --}}
        <div class="row g-3 mb-4">
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.total_products') }}</div>
                    <div class="fs-lg fw-bold fg-body mt-1">{{ $summary['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.visible') }}</div>
                    <div class="fs-lg fw-bold fg-success mt-1">{{ $summary['visible'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.hidden') }}</div>
                    <div class="fs-lg fw-bold fg-secondary mt-1">{{ $summary['hidden'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.in_stock') }}</div>
                    <div class="fs-lg fw-bold fg-primary mt-1">{{ $summary['in_stock'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.low_stock_5') }}</div>
                    <div class="fs-lg fw-bold fg-emphasis-warning mt-1">{{ $summary['low_stock'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 md:col-4 xl:col-2">
                <div class="card border-0 shadow-sm rounded-9 p-3 h-100 bg-white">
                    <div class="fg-3 small fw-semibold">{{ __('management.out_of_stock') }}</div>
                    <div class="fs-lg fw-bold fg-danger mt-1">{{ $summary['out_of_stock'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom mb-4">
                <div>
                    <h3 class="fw-bold fg-body mb-1">
                        <i class="bi bi-box-seam fg-primary me-2"></i>{{ __('management.store_products_inventory') }}
                    </h3>
                    <p class="fg-3 small mb-0">
                        {{ __('management.copy.products_intro', ['store' => $vendor->store_name]) }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('vendor.products.create') }}" class="btn btn-solid theme-primary rounded-pill px-4 shadow-sm fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('management.new_product_listing') }}
                    </a>
                </div>
            </div>

            {{-- Filter & Search Bar --}}
            <form action="{{ route('vendor.products.index') }}" method="GET" class="row g-2 align-items-end mb-4">
                <div class="md:col-4">
                    <label for="filter-q" class="form-label small fg-3 fw-semibold mb-1">{{ __('management.search') }}</label>
                    <input
                        type="search"
                        id="filter-q"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        class="form-control rounded-pill"
                        placeholder="{{ __('management.search_name_or_slug') }}"
                        maxlength="100"
                    >
                </div>
                <div class="md:col-3">
                    <label for="filter-category" class="form-label small fg-3 fw-semibold mb-1">{{ __('management.category') }}</label>
                    <select id="filter-category" name="category_id" class="form-control rounded-pill">
                        <option value="">{{ __('management.all_categories') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->category_id }}" @selected(($filters['category_id'] ?? null) === (int) $cat->category_id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-3">
                    <label for="filter-availability" class="form-label small fg-3 fw-semibold mb-1">{{ __('management.availability_stock') }}</label>
                    <select id="filter-availability" name="availability" class="form-control rounded-pill">
                        @foreach ($availabilityFilters as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['availability'] ?? 'all') === $key)>
                                {{ __('management.availability.'.$key) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-2 d-flex gap-2">
                    <button type="submit" class="btn btn-solid theme-inverse rounded-pill w-100">{{ __('management.filter') }}</button>
                    @if (! empty($filters['q']) || ! empty($filters['category_id']) || ($filters['availability'] ?? 'all') !== 'all')
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-outline theme-secondary rounded-pill" title="{{ __('management.clear_filters') }}">{{ __('management.reset') }}</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-1">
                        <tr>
                            <th scope="col" style="width: 72px;">{{ __('management.image') }}</th>
                            <th scope="col">{{ __('management.product') }}</th>
                            <th scope="col">{{ __('management.category') }}</th>
                            <th scope="col">{{ __('management.price') }}</th>
                            <th scope="col" style="width: 190px;">{{ __('management.inventory_uc_33') }}</th>
                            <th scope="col">{{ __('management.visibility') }}</th>
                            <th scope="col" class="text-end" style="width: 280px;">{{ __('management.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $primaryImage = $product->images->first();
                            @endphp
                            <tr class="{{ ! $product->is_active ? 'bg-1 opacity-75' : '' }}">
                                <td>
                                    <div class="media-frame ratio-1x1 rounded-5 overflow-hidden bg-1" style="width: 56px;">
                                        @if ($primaryImage)
                                            <img src="{{ $primaryImage->url }}" class="object-fit-cover w-100 h-100" alt="{{ $product->name }}">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center fg-3">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold fg-body">{{ $product->name }}</div>
                                    <small class="fg-3">
                                        #{{ $product->product_id }} &bull; {{ $product->images->count() }} image(s)
                                        @if ($product->status !== 'approved')
                                            &bull; <span class="badge bg-subtle-warning fg-emphasis-warning rounded-pill">{{ __('products.vendor_status.'.$product->status) }}</span>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-1 fg-body border">
                                        {{ $product->category?->name ?? __('management.uncategorized') }}
                                    </span>
                                </td>
                                <td class="fw-semibold fg-success">
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
                                            aria-label="{{ __('management.stock_for', ['name' => $product->name]) }}"
                                        >
                                        <button type="submit" class="btn btn-sm btn-outline theme-inverse rounded-pill px-2" title="{{ __('management.save_stock') }}">
                                            {{ __('management.save') }}
                                        </button>
                                    </form>
                                    <div class="mt-1">
                                        @if ($product->stock === 0)
                                            <span class="badge bg-subtle-danger fg-danger rounded-pill">{{ __('management.out_of_stock') }}</span>
                                        @elseif ($product->stock <= 5)
                                            <span class="badge bg-subtle-warning fg-emphasis-warning rounded-pill">{{ __('management.low_stock') }}</span>
                                        @else
                                            <span class="badge bg-subtle-success fg-success rounded-pill">{{ __('management.in_stock') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($product->is_active)
                                        <span class="badge bg-subtle-success fg-success border border-success rounded-pill px-2 py-1">
                                            <i class="bi bi-eye me-1"></i>{{ __('management.visible') }}
                                        </span>
                                    @else
                                        <span class="badge bg-subtle-secondary fg-secondary border rounded-pill px-2 py-1">
                                            <i class="bi bi-eye-slash me-1"></i>{{ __('management.hidden') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                        {{-- UC-31: Edit Product --}}
                                        <a href="{{ route('vendor.products.edit', $product->product_id) }}" class="btn btn-sm btn-outline theme-secondary rounded-pill px-3" title="{{ __('management.edit_product_listing') }}">
                                            <i class="bi bi-pencil me-1"></i>{{ __('management.edit') }}
                                        </a>

                                        {{-- UC-31 & UC-33: Hide / Show Product --}}
                                        <form action="{{ route('vendor.products.toggle-active', $product->product_id) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            @if ($product->is_active)
                                                <button type="submit" class="btn btn-sm btn-outline theme-warning rounded-pill px-3" title="{{ __('management.hide_product_from_public_catalog') }}">
                                                    <i class="bi bi-eye-slash me-1"></i>{{ __('management.hide') }}
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-outline theme-success rounded-pill px-3" title="{{ __('management.show_product_in_public_catalog') }}">
                                                    <i class="bi bi-eye me-1"></i>{{ __('management.show') }}
                                                </button>
                                            @endif
                                        </form>

                                        {{-- UC-32: Manage Images --}}
                                        <a href="{{ route('vendor.products.images.index', $product->product_id) }}" class="btn btn-sm btn-outline theme-primary rounded-pill px-3" title="{{ __('management.manage_product_images') }}">
                                            <i class="bi bi-images me-1"></i>{{ __('management.images') }}
                                        </a>

                                        @if ($product->is_active && $product->status === 'approved')
                                            <a href="{{ route('products.show', [$product->product_id, $product->slug]) }}" target="_blank" class="btn btn-sm btn-solid theme-secondary rounded-pill px-2" title="{{ __('management.view_on_storefront') }}">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 fg-3">
                                    <i class="bi bi-box2 fs-3xl d-block mb-3 fg-secondary"></i>
                                    {{ __('management.copy.empty_products') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="mt-4 pt-3 border-top">
                    {{ $products->links('shared.pagination') }}
                </div>
            @endif
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
