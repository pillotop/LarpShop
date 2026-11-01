@props([
    'productCategories',
    'productCartBsTarget' => '',
    'productCartBsToggle' => '',
    'cart'
])

<nav class="navigation-bar position-relative z-1 py-2">
    <div class="row gap-1 lg:gap-3">
        <div class="col-1 d-flex align-items-center justify-content-center">
            <a
                href="{{ route('home') }}"
                aria-label="{{ __('common.home') }}"
            >
                <img src="/images/larp_shop.jpg" class="rounded-circle logo-larpshop-logo">
            </a>
        </div>
        <div id="search-bar" class="col-7">
            <form
                action="{{ route('products.index') }}"
                method="GET"
                class="d-flex rounded-9 h-100"
                role="search"
            >
                <button type="button" class="d-flex gap-2 justify-content-between align-items-center rounded-start-pill border-0 px-3" aria-label="{{ __('common.select_category') }}" data-dropdown-trigger="categories">
                    <span>{{ __('common.categories') }}</span>
                    <svg class="dropdown-arrow icon-16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <path class="dropdown-arrow-path" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                    </svg>
                    <template data-dropdown-template="categories">
                        <div class="category-dropdown-menu rounded-5 shadow">
                            @foreach ($productCategories as $category)
                                <a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    </template>
                </button>
                <div class="d-flex align-items-center position-relative w-100">
                    <input
                        type="search"
                        name="q"
                        value="{{ request()->query('q', '') }}"
                        placeholder="{{ __('common.search_placeholder') }}"
                        aria-label="{{ __('common.search_products') }}"
                        maxlength="100"
                        class="position-relative border ps-3 z-0 w-100 h-100"
                    >
                    <button
                        type="submit"
                        class="border-0 rounded-circle position-absolute z-1 end-0 me-2 py-1 px-2"
                        aria-label="{{ __('common.search_products') }}"
                    >
                        <svg class="icon-16">
                            <use href="/images/icons.svg#search-magnifier"></use>
                        </svg>
                    </button>
                </div>
                <button class="rounded-end-pill border-0 p-2 px-3" type="button">
                    <span>{{ __('common.advanced') }}</span>
                </button>
            </form>
        </div>
        <div class="col-12 lg:col-3 d-flex align-items-center justify-content-between">
            <button
                id="product-cart"
                class="side-panel-trigger border-0 rounded-pill p-2"
                type="button"
                data-cart-show-url="{{ route('cart.show') }}"
                data-cart-add-url="{{ route('cart.add') }}"
                data-cart-set-url="{{ route('cart.store') }}"
                data-bs-target="{{ $productCartBsTarget }}"
                data-bs-toggle="{{ $productCartBsToggle }}"
                data-side-panel-target="#cart-section"
            >
                <div class="position-relative">
                    <svg class="position-relative icon-24">
                        <use href="/images/icons.svg#cart"></use>
                    </svg>
                    <span
                        data-cart-count
                        class="position-absolute badge bg-primary end-0 top-0"
                        aria-label="{{ __('common.items_in_cart') }}">{{ $cart['total_count'] }}
                    </span>
                </div>
                <span data-cart-total>${{ $cart['total_amount'] }}</span>

                <template id="cart-section">
                    <x-cart :data="$cart"/>
                </template>
            </button>
            <span data-cart-error class="small fg-danger" role="status" aria-live="polite"></span>
            @guest
                <a
                    href="{{ route('login') }}"
                    class="rounded-pill p-2 text-decoration-none"
                >
                    <span class="text-decoration-underline">
                        <b>{{ __('common.login') }}</b>
                        {{ __('common.or') }}
                        <b>{{ __('common.register') }}</b>
                    </span>
                </a>
            @endguest
            @auth
                <div class="position-relative">
                    <button class="border-0 rounded-pill px-3 py-2 d-flex align-items-center gap-2 bg-1 shadow-sm" type="button" data-bs-toggle="menu" data-bs-placement="bottom-end" aria-expanded="false">
                        <i class="bi bi-person-circle fs-lg fg-primary"></i>
                        <span class="fw-semibold text-truncate" style="max-width: 120px;">{{ Auth::user()->username }}</span>
                        <svg class="icon-16" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                        </svg>
                    </button>
                    <div class="menu  shadow border-0 rounded-5 p-2 mt-1" style="min-width: 220px;">

                        @if (Auth::user()->isAdmin())

                                <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2 fg-danger fw-semibold" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>{{ __('common.admin_dashboard') }}</span>
                                </a>

                        @endif

                            <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2" href="{{ route('profile') }}">
                                <i class="bi bi-person-gear"></i>
                                <span>{{ __('common.profile') }}</span>
                            </a>


                            <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2" href="{{ route('profile') }}#pane-vendor">
                                <i class="bi bi-shop-window"></i>
                                <span>{{ __('common.my_stores') }}</span>
                            </a>

                        @if (Auth::user()->isVendor())

                                <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2 fg-primary fw-semibold" href="{{ route('vendor.products.index') }}">
                                    <i class="bi bi-box-seam"></i>
                                    <span>{{ __('common.my_products') }}</span>
                                </a>


                                <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2" href="{{ route('vendor.orders.index') }}">
                                    <i class="bi bi-truck"></i>
                                    <span>{{ __('common.store_orders') }}</span>
                                </a>


                                <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2" href="{{ route('vendor.categories.index') }}">
                                    <i class="bi bi-tags"></i>
                                    <span>{{ __('common.my_categories') }}</span>
                                </a>


                                <a class="menu-item rounded-4 py-2 d-flex align-items-center gap-2" href="{{ route('vendor.reports.index') }}">
                                    <i class="bi bi-graph-up"></i>
                                    <span>{{ __('common.store_reports') }}</span>
                                </a>

                        @endif
                        <hr class="menu-divider my-1">

                            <form action="{{ route('logout') }}" method="post" class="m-0">
                                @csrf
                                <button type="submit" class="menu-item rounded-4 py-2 fg-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>{{ __('common.logout') }}</span>
                                </button>
                            </form>

                    </div>
                </div>
            @endauth
        </div>
    </div>
</nav>

<script type="application/json" id="cart-initial-state">@json($cart)</script>
