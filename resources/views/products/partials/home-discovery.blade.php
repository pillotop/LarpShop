<div
    data-discovery-result
    data-expanded="{{ $expanded ? 'true' : 'false' }}"
    data-has-more="{{ $hasMore ? 'true' : 'false' }}"
    tabindex="-1"
    role="region"
    aria-label="{{ __('products.discovery.results') }}"
>
    @if ($products->isNotEmpty())
        <x-product-grid :products="$products" />
    @else
        <p class="text-center text-body-secondary py-5">
            {{ __('products.discovery.empty') }}
        </p>
    @endif
</div>
