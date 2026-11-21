@props(['product'])

<x-deferred-section
    :source="route('products.similar', $product)"
    :label="__('products.detail.loading_similar')"
    :error-message="__('products.detail.load_similar_error')"
    data-deferred-similar-products
>
    <div class="container my-5">
        <h2 class="h4 fw-bold mb-3">{{ __('products.detail.similar') }}</h2>
        <div class="d-flex gap-3 overflow-hidden">
            @for ($index = 0; $index < 4; $index++)
                <div class="flex-shrink-0" style="width: 260px;">
                    <x-product-card-skeleton />
                </div>
            @endfor
        </div>
    </div>
</x-deferred-section>
