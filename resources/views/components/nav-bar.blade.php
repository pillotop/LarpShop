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
                        <div class="dropdown-menu rounded-3 shadow">
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
                data-bs-target="{{ $productCartBsTarget }}"
                data-bs-toggle="{{ $productCartBsToggle }}"
                data-side-panel-target="#cart-section"
            >
                <svg class="icon-16">
                    <use href="/images/icons.svg#cart"></use>
                </svg>
                <span>$0.00</span>

                <template id="cart-section">
                    <div class="p-2 position-relative h-100">
                        <div class="offcanvas-header">
                            <button class="btn-close" type="button" data-bs-dismiss="offcanvas"></button>
                        </div>
                        @if ($cart['items']->isNotEmpty())
                            Foo
                        @else
                            <div class="text-center position-relative top-50 translate-middle-y">
                                <img src="https://m.media-amazon.com/images/G/01/cart/empty/kettle-desaturated._CB445243794_.svg">
                                <h4>Your shopping bag is empty...</h4>
                                <span>When you add products to your shopping bag, they will appear here.</span>
                            </div>

                            <div class="position-absolute bottom-0">
                                <span>Can't find your products? Make sure you're logged in.</span>
                            </div>
                        @endif
                    </div>
                </template>
            </button>
            @guest
                <a href="/login">
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
