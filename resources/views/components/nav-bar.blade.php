@props([
    'productCategories',
    'productCartBsTarget' => '',
    'productCartBsToggle' => '',
    'cart'
])

<nav class="navigation-bar position-relative z-1 py-2">
    <div class="row gap-1 gap-lg-3">
        <div class="col-1 d-flex align-items-center justify-content-center">
            <a href="{{ route('home') }}">
                <img src="/images/larp_shop.jpg" class="rounded-circle logo-larpshop-logo">
            </a>
        </div>
        <div id="search-bar" class="col-7">
            <form
                action="{{ route('products.index') }}"
                method="GET"
                class="d-flex rounded-4 h-100"
                role="search"
            >
                <button type="button" class="d-flex gap-2 justify-content-between align-items-center rounded-start-pill border-0 px-3" aria-label="cateogry selection" data-dropdown-trigger="categories">
                    <span>Categories</span>
                    <svg class="dropdown-arrow icon-16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <path class="dropdown-arrow-path" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                    </svg>
                    <template data-dropdown-template="categories">
                        <div class="category-dropdown-menu rounded-3 shadow">
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
                        placeholder="Search products..."
                        aria-label="Search products"
                        maxlength="100"
                        class="position-relative border ps-3 z-0 w-100 h-100"
                    >
                    <button type="submit" class="border-0 rounded-circle position-absolute z-1 end-0 me-2 py-1 px-2">
                        <svg class="icon-16">
                            <use href="/images/icons.svg#search-magnifier"></use>
                        </svg>
                    </button>
                </div>
                <button class="rounded-end-pill border-0 p-2 px-3" type="button">
                    <span>Advanced</span>
                </button>
            </form>
        </div>
        <div class="col-12 col-lg-3 d-flex align-items-center justify-content-between">
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
                        aria-label="Items in cart">{{ $cart['total_count'] }}
                    </span>
                </div>
                <span data-cart-total>${{ $cart['total_amount'] }}</span>

                <template id="cart-section">
                    <x-cart :data="$cart"/>
                </template>
            </button>
            <span data-cart-error class="small text-danger" role="status" aria-live="polite"></span>
            @guest
                <a href="{{ route('login') }}">
                    <button class="border-0 rounded-pill p-2" type="submit">
                        <span class="text-decoration-underline"><b>Login</b> or <b>Register</b></span>
                    </button>
                </a>
            @endguest
            @auth
                <div class="dropdown">
                    <button class="border-0 rounded-pill px-3 py-2 d-flex align-items-center gap-2 bg-light shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <span class="fw-semibold text-truncate" style="max-width: 120px;">{{ Auth::user()->username }}</span>
                        <svg class="icon-16" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M5.75 8.75 L10 13 L14.25 8.75"></path>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2 mt-1" style="min-width: 220px;">

                        @if (Auth::user()->isAdmin())
                            <li>
                                <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 text-danger fw-semibold" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Bảng điều khiển Admin</span>
                                </a>
                            </li>
                        @endif
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="{{ route('profile') }}">
                                <i class="bi bi-person-gear"></i>
                                <span>Hồ sơ tài khoản</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="{{ route('profile') }}#pane-vendor">
                                <i class="bi bi-shop-window"></i>
                                <span>My stores</span>
                            </a>
                        </li>
                        @if (Auth::user()->isVendor())
                            <li>
                                <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 text-primary fw-semibold" href="{{ route('vendor.products.index') }}">
                                    <i class="bi bi-box-seam"></i>
                                    <span>Sản phẩm của tôi</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="{{ route('vendor.orders.index') }}">
                                    <i class="bi bi-truck"></i>
                                    <span>Store Orders</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="{{ route('vendor.categories.index') }}">
                                    <i class="bi bi-tags"></i>
                                    <span>Danh mục của tôi</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="{{ route('vendor.reports.index') }}">
                                    <i class="bi bi-graph-up"></i>
                                    <span>Store Reports</span>
                                </a>
                            </li>
                        @endif
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="post" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-2 py-2 text-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Đăng xuất</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>
</nav>

<script type="application/json" id="cart-initial-state">@json($cart)</script>
