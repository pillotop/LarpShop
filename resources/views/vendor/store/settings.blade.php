@extends('layouts.app')

@section('title', 'Store Settings (UC-29) | ' . $vendor->store_name)

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
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile') }}">Account Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Store Settings</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

        @if (session('success'))
            <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-9 p-3 mb-4 shadow-sm" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="lg:col-4">
                <div class="card border-0 shadow-sm rounded-9 p-4 text-center bg-white mb-4">
                    <div class="rounded-circle bg-primary bg-10 d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-shop fs-3xl fg-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-1">{{ $vendor->store_name }}</h5>
                    <div class="mb-3">
                        @if ($vendor->status === 'active' || $vendor->status === 'approved')
                            <span class="badge bg-subtle-success fg-success border border-success rounded-pill px-3 py-1">
                                <i class="bi bi-check-circle me-1"></i>Active
                            </span>
                        @elseif ($vendor->status === 'pending')
                            <span class="badge bg-subtle-warning fg-emphasis-warning border border-warning rounded-pill px-3 py-1">
                                <i class="bi bi-clock-history me-1"></i>Pending Approval
                            </span>
                        @else
                            <span class="badge bg-subtle-secondary fg-secondary border rounded-pill px-3 py-1">
                                {{ ucfirst($vendor->status) }}
                            </span>
                        @endif
                    </div>

                    <div class="d-flex justify-content-around py-3 border-top border-bottom mb-3 text-start">
                        <div>
                            <small class="fg-3 d-block">Products</small>
                            <span class="fw-bold fs-lg">{{ $vendor->products_count ?? 0 }}</span>
                        </div>
                        <div>
                            <small class="fg-3 d-block">Store ID</small>
                            <span class="fw-bold fs-lg">#{{ $vendor->vendor_id }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('vendor.products.index') }}" class="btn btn-solid theme-primary rounded-pill">
                            <i class="bi bi-box-seam me-1"></i>Products & Inventory
                        </a>
                        <a href="{{ route('vendors.show', $vendor->vendor_id) }}" class="btn btn-outline theme-primary rounded-pill" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-1"></i>View Public Storefront
                        </a>
                        <a href="{{ route('vendor.categories.index') }}" class="btn btn-solid theme-secondary rounded-pill">
                            <i class="bi bi-tags me-1"></i>Platform Categories
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-8">
                <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white">
                    <div class="border-bottom pb-3 mb-4">
                        <h4 class="fw-bold fg-body mb-1">
                            <i class="bi bi-gear-wide-connected fg-primary me-2"></i>Store Configuration (UC-29)
                        </h4>
                        <p class="fg-3 small mb-0">Update your store name and public brand description.</p>
                    </div>

                    <form action="{{ route('vendor.store.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="store_name" class="form-label fw-semibold">Store Name <span class="fg-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control form-control-lg rounded-5 @error('store_name') is-invalid @enderror"
                                id="store_name"
                                name="store_name"
                                value="{{ old('store_name', $vendor->store_name) }}"
                                required
                            >
                            @error('store_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Store names must be unique across LarpShop.</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">Store Description</label>
                            <textarea
                                class="form-control rounded-5 @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Describe your store's specialty, craftsmanship, and policies..."
                            >{{ old('description', $vendor->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('profile') }}" class="btn btn-solid theme-secondary rounded-pill px-4">
                                <i class="bi bi-arrow-left me-1"></i>Back to Profile
                            </a>
                            <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4 py-2 fw-semibold">
                                <i class="bi bi-save me-1"></i>Save Store Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
