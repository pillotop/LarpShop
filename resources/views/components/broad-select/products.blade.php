<div
    id="{{ $panelId }}"
    data-broad-select-panel="{{ $group->key }}"
    role="tabpanel"
    aria-labelledby="{{ $instanceId }}-tab-{{ $loop->index }}"
    tabindex="0"
    @if (! $active) hidden @endif
>
    @if ($group->viewAllUrl)
        <div class="text-end px-3 mb-2">
            <a
                href="{{ $group->viewAllUrl }}"
                class="text-decoration-none fw-semibold"
            >
                {{ __('promotions.view_all') }}
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    @endif
    @if ($products->isEmpty())
        <p class="p-3 fg-2">{{ __('promotions.no_products') }}</p>
    @else
        <div
            class="carousel-scrollable w-100 overflow-hidden"
            data-carousel-direction="right"
            data-carousel-autoplay-speed="60"
        >
            <ul
                class="selector-product-list carousel-scroller"
                tabindex="0"
                aria-label="{{ __('promotions.products_in_group', ['group' => $group->label]) }}"
            >
                @foreach ($products as $product)
                    <li>
                        <x-product-card-a :product="$product" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
