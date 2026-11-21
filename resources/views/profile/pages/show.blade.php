@extends('layouts.app')

@section('title', __('profile.title'))

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/profile.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/product_quantity_picker.js',
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
        'resources/js/components/profile_tabs.js',
    ])
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    @php
        $initialTab = match (true) {
            session('status') === 'wishlist-updated' => 'tab-wishlist',
            $errors->billing->any() => 'tab-billing',
            $errors->hasAny(['current_password', 'password', 'password_confirmation']) || session('status') === 'password-updated' => 'tab-security',
            $errors->hasAny(['username', 'email', 'phone_number', 'date_of_birth', 'gender', 'profile_image', 'remove_profile_image']) || session('status') === 'profile-updated' => 'tab-profile-details',
            $errors->hasAny(['store_name', 'description']) || in_array(session('status'), ['vendor-registered', 'vendor-updated']) => 'tab-vendor-info',
            request()->hasAny(['orders_q', 'orders_filter', 'orders_period', 'orders_page']) => 'tab-purchases',
            default => '',
        };
    @endphp

    <main
        class="container my-4"
        data-profile-tabs
        data-initial-tab="{{ $initialTab }}"
    >
        {{-- Breadcrumbs Navigation --}}
        <nav aria-label="breadcrumb" class="mb-4">
            {{ Breadcrumbs::render('profile') }}
        </nav>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert theme-warning alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-lg fg-warning"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold">{{ session('warning') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill fs-lg fg-danger"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">{{ __('profile.check_information') }}</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.close') }}"></button>
            </div>
        @endif

        {{-- Profile Hero Card --}}
        <x-profile.hero-card :user="$user" :vendors="$vendors" />

        {{-- Navigation Tabs --}}
        <div class="row g-4">
            <div class="col-12 lg:col-3">
                <div class="card border-0 shadow-sm rounded-9 p-3 lg:sticky-top" style="top: 100px;">
                    <x-profile.nav-tabs
                        id="profileTabs"
                        :panes="[
                            [
                                'id' => 'tab-customer-info',
                                'target_id' => '#pane-account',
                                'aria_controls' => 'pane-account',
                                'icon' => 'bi-person-lines-fill',
                                'label' => __('profile.account'),
                            ],
                            [
                                'id' => 'tab-vendor-info',
                                'target_id' => '#pane-vendor',
                                'aria_controls' => 'pane-vendor',
                                'icon' => 'bi-shop-window',
                                'label' => __('profile.stores'),
                            ],
                            [
                                'id' => 'tab-purchases',
                                'target_id' => '#pane-purchases',
                                'aria_controls' => 'pane-purchases',
                                'icon' => 'bi-bag-check',
                                'label' => __('profile.purchases.tab'),
                            ],
                            [
                                'id' => 'tab-wishlist',
                                'target_id' => '#pane-wishlist',
                                'aria_controls' => 'pane-wishlist',
                                'icon' => 'bi-heart',
                                'label' => __('wishlist.title'),
                            ],
                        ]"
                    />

                    <hr class="my-3 fg-3">

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline theme-danger w-100 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('common.logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 lg:col-9">
                <div class="tab-content" id="profileTabContent">
                    <x-profile.account-tab
                        :user="$user"
                        :addresses="$addresses"
                    />

                    {{-- Tab 2: Vendor Store Profile / Register --}}
                    <x-profile.vendor-tab :vendors="$vendors" />

                    {{-- Tab 2b: Purchase history --}}
                    <x-profile.purchases-tab
                        :orders="$purchases"
                        :filters="$purchaseFilters"
                        :reviewable-product-ids="$reviewableProductIds"
                    />

                    <x-profile.wishlist-tab :products="$wishlistProducts" />
                </div>
            </div>
        </div>
    </main>

    <x-side-panel />

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
