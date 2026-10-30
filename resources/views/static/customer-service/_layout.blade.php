@extends('layouts.app')

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/top_header.css',
        'resources/css/components/navigation_bar.css',
    ])
    <style>
        .cs-hero {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 60%, #415a77 100%);
            color: #ffffff;
            border-radius: 1.5rem;
            padding: 3.5rem 2rem;
        }
        .cs-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .cs-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }
        .cs-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
    </style>
    @yield('page-head')
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
    ])
    @yield('page-tail')
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
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories"
            :cart="$cart"
        />
    </header>

    <main class="container my-4">
        <div class="mb-4">
            {{ Breadcrumbs::render($breadcrumb) }}
        </div>

        @yield('page-content')
    </main>

    <x-side-panel/>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
