@extends('layouts.app')

@section('title', 'Cart')

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
    <script src="/js/components/product_quantity_picker.js"></script>
@endsection

@section('content')
    <header class="container sticky-top" style="background-color: #ffffff99;">
        <link rel="stylesheet" href="/css/top_header.css">
        <link rel="stylesheet" href="/css/components/navigation_bar.css">
        <x-nav-bar product-cart-bs-target="#side-panel" product-cart-bs-toggle="offcanvas" :product-categories="$productCategories" :cart="$cart" />
        <div class="persistent-header position-relative mt-2 px-4 py-2 z-0">
            <ul class="persistent-header-content d-flex align-items-center gap-3 p-0 overflow-scroll" style="list-style: none;">
                <li>
                    <button type="button" class="side-panel-trigger nav-button rounded-pill border-0" data-bs-target="#side-panel" data-bs-toggle="offcanvas" data-side-panel-target="#settings">
                        <svg class="icon-16">
                            <use href="/images/icons.svg#settings-sliders"></use>
                        </svg>
                        <span> Settings </span>
                        <template id="settings">
                            @auth
                                <span class="text-center">Sad setting form with nothing other than the logout button</span>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button type="submit" class="nav-button rounded-pill border-0">
                                        Logout
                                    </button>
                                </form>
                            @endauth
                        </template>
                    </button>
                </li>
                <li>
                    <button type="button" class="nav-button rounded-pill border-0">
                        Parcel delivery
                    </button>
                </li>
                <li>
                    <button type="button" class="nav-button rounded-pill border-0">
                        Phone sales service
                    </button>
                </li>
                <li>
                    <button type="button" class="nav-button rounded-pill border-0">
                        Return policy
                    </button>
                </li>
            </ul>
        </div>
        <div id="dropdown-portal"></div>
    </header>
    <main>

        <div class="container" data-cart-page>
            @include('cart.partials.content', ['cart' => $cart])
        </div>
    </main>
    <x-company-footer />
    <x-side-panel />
@endsection
