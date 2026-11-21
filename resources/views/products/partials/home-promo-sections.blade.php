<div
    data-deferred-fragment
    data-deferred-focus
    data-home-promo-sections
    role="region"
    aria-label="{{ __('promotions.featured_sections') }}"
    tabindex="-1"
>
    @foreach ($promoSections as $promo)
        <div class="container my-4">
            <x-promo-section
                :data="$promo"
                :load-assets="false"
            />
        </div>
    @endforeach
</div>
