<section
    class="container my-5"
    data-deferred-fragment
    data-deferred-focus
    tabindex="-1"
    aria-label="{{ __('products.detail.similar') }}"
>
    <h2 class="h4 fw-bold mb-3">{{ __('products.detail.similar') }}</h2>
    @if ($similarProducts->isNotEmpty())
        <x-two-arrow-scroller>
            <ul
                data-scroll-content
                class="d-flex flex-nowrap gap-3 list-unstyled overflow-x-auto m-0 py-2"
            >
                @foreach ($similarProducts as $similarProduct)
                    <li>
                        <x-product-card-b :product="$similarProduct" />
                    </li>
                @endforeach
            </ul>
        </x-two-arrow-scroller>
    @else
        <p class="p-4 rounded-2 text-center text-body-secondary">
            {{ __('products.detail.no_similar') }}
        </p>
    @endif
</section>
