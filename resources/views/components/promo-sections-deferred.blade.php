<x-deferred-section
    :source="route('home.promo-sections')"
    :label="__('promotions.loading_sections')"
    :error-message="__('promotions.load_sections_error')"
    data-kind="promo-sections"
    data-deferred-promo-sections
>
    <div class="container py-4">
        <span class="placeholder rounded-2 col-5 mb-4"></span>
        <div class="row g-4">
            @for ($index = 0; $index < 4; $index++)
                <div class="col-6 lg:col-3">
                    <x-product-card-skeleton />
                </div>
            @endfor
        </div>
    </div>
</x-deferred-section>
