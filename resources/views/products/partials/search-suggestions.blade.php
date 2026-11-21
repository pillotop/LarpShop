@if ($products->isNotEmpty())
    <div role="group" aria-label="{{ __('common.search_products') }}">
        <div class="search-suggestions-heading" role="presentation">{{ __('common.search_products') }}</div>
        @foreach ($products as $product)
            @php
                $path = $product->images->first()?->path;
                $image = $path
                    ? (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']) ? $path : asset('storage/' . ltrim($path, '/')))
                    : asset('images/product-placeholder.svg');
            @endphp
            <a
                href="{{ route('products.show', ['product' => $product->getKey(), 'slug' => $product->slug]) }}"
                class="search-suggestion d-flex align-items-center gap-3"
                role="option"
                aria-selected="false"
                tabindex="-1"
            >
                <img src="{{ $image }}" width="44" height="44" alt="" loading="lazy">
                <span class="flex-grow-1 text-break">{{ $product->name }}</span>
                <span class="fw-semibold text-nowrap">{{ $money->format($product->price) }}</span>
            </a>
        @endforeach
    </div>
@endif

@if ($categories->isNotEmpty())
    <div role="group" aria-label="{{ __('common.categories') }}">
        <div class="search-suggestions-heading" role="presentation">{{ __('common.categories') }}</div>
        @foreach ($categories as $suggestedCategory)
            <a
                href="{{ route('products.index', ['q' => $keyword, 'category' => $suggestedCategory->slug]) }}"
                class="search-suggestion d-flex align-items-center gap-3"
                role="option"
                aria-selected="false"
                tabindex="-1"
            >
                <i class="bi bi-grid" aria-hidden="true"></i>
                <span class="text-break">{{ $suggestedCategory->name }}</span>
                <i class="bi bi-arrow-up-right ms-auto" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif

<a
    href="{{ route('products.index', array_filter(['q' => $keyword, 'category' => $category], fn ($value) => $value !== '')) }}"
    class="search-suggestion border-top d-flex align-items-center gap-3"
    role="option"
    aria-selected="false"
    tabindex="-1"
>
    <i class="bi bi-search" aria-hidden="true"></i>
    <span class="text-break">{{ __('common.search_all_results', ['keyword' => $keyword]) }}</span>
    <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
</a>
