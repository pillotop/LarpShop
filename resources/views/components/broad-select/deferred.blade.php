<x-deferred-section
    :source="route('home.broad-select')"
    :label="__('promotions.loading_products')"
    :error-message="__('promotions.load_error')"
    data-kind="broad-select"
    data-deferred-broad-select
>
    <div class="broad-select">
        <header class="container text-center my-3">
            <h2>{{ __('products.home.heading') }}</h2>
            <p>{{ __('products.home.intro') }}</p>
        </header>
        <div class="selector">
            <div class="broad-select-placeholder-choices">
                @for ($index = 0; $index < 3; $index++)
                    <span class="placeholder rounded-2"></span>
                @endfor
            </div>
            <div class="broad-select-placeholder-products">
                @for ($index = 0; $index < 4; $index++)
                    <x-product-card-skeleton variant="a" />
                @endfor
            </div>
        </div>
    </div>
</x-deferred-section>
