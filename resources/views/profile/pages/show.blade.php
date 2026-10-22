@extends('layouts.app')

@section('title', 'Hồ sơ tài khoản | LarpShop')

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
    <link rel="stylesheet" href="/css/profile.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const showTab = (id) => {
                const button = document.getElementById(id);
                if (button) bootstrap.Tab.getOrCreateInstance(button).show();
            };
            const hash = window.location.hash;
            const trigger = Array.from(document.querySelectorAll('#profileTabs [data-bs-target], #vendorTabs [data-bs-target]'))
                .find(button => button.dataset.bsTarget === hash);

            if (trigger) {
                if (trigger.closest('#vendorTabs')) showTab('tab-vendor-info');
                showTab(trigger.id);
            } else if (@json($errors->has('store_name') || $errors->has('description') || in_array(session('status'), ['vendor-registered', 'vendor-updated']))) {
                showTab('tab-vendor-info');
            } else if (@json($errors->has('current_password') || $errors->has('password') || $errors->has('password_confirmation') || session('status') === 'password-updated')) {
                showTab('tab-security');
            }
        });
    </script>
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
            {{ Breadcrumbs::render('profile') }}
        </nav>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                <div class="flex-grow-1">
                    <span class="fw-semibold">{{ session('warning') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Profile Hero Card --}}
        <x-profile.hero-card :user="$user" :vendors="$vendors" />

        {{-- Navigation Tabs --}}
        <div class="row g-4">
            <div class="col-12 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 sticky-top" style="top: 100px;">
                    <x-profile.nav-tabs
                        id="profileTabs"
                        :panes="[
                            [
                                'id' => 'tab-customer-info',
                                'target_id' => '#pane-customer',
                                'aria_controls' => 'pane-customer',
                                'icon' => 'bi-person-lines-fill',
                                'label' => 'Personal profile',
                            ],
                            [
                                'id' => 'tab-vendor-info',
                                'target_id' => '#pane-vendor',
                                'aria_controls' => 'pane-vendor',
                                'icon' => 'bi-shop-window',
                                'label' => 'Stores',
                            ],
                            [
                                'id' => 'tab-security',
                                'target_id' => '#pane-security',
                                'aria_controls' => 'pane-security',
                                'icon' => 'bi-shield-lock',
                                'label' => 'Change password',
                            ],
                        ]"
                    />

                    <hr class="my-3 text-muted">

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Đăng xuất</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-9">
                <div class="tab-content" id="profileTabContent">
                    {{-- Tab 1: Customer Personal Profile --}}
                    <x-profile.profile-tab :user="$user" />

                    {{-- Tab 2: Vendor Store Profile / Register --}}
                    <x-profile.vendor-tab :vendors="$vendors" />

                    {{-- Tab 3: Security & Password Update --}}
                    <x-profile.security-tab />
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
