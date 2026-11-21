<header class="auth-page-header border-bottom">
    <nav class="container d-flex align-items-center justify-content-between gap-3 py-3" aria-label="{{ __('common.home') }}">
        <a
            href="{{ route('home') }}"
            class="d-inline-flex align-items-center gap-3 text-decoration-none fg-body"
        >
            <img src="{{ asset('images/larp_shop.png') }}" width="40" height="40" alt="">
            <span class="fw-semibold">LarpShop</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('products.index') }}" class="small fg-body">
                {{ __('auth.page.products') }}
            </a>
            <x-language-trigger />
        </div>
    </nav>
</header>
