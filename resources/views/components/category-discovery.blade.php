@props(['categories'])

<section
    class="category-discovery py-5 mt-4"
    data-category-discovery
    data-source="{{ route('home.discovery') }}"
    aria-labelledby="category-discovery-heading"
>
    <div class="container">
        <header class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 id="category-discovery-heading" class="h3 fw-bold mb-2">
                    {{ __('products.home.everything') }}
                </h2>
                <p class="text-body-secondary mb-0">{{ __('products.home.description') }}</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-decoration-none text-nowrap">
                {{ __('products.home.browse') }}
                <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </header>

        <nav class="category-discovery-categories mb-4" aria-label="{{ __('products.filters.category') }}">
            <x-two-arrow-scroller :plain-arrows="true" scroll-step="0.6">
                <ul class="d-flex gap-2 list-unstyled overflow-x-auto m-0 py-2 px-4" data-scroll-content>
                    <li>
                        <a
                            href="{{ route('products.index') }}"
                            class="category-discovery-choice rounded-2"
                            data-discovery-category=""
                            aria-current="true"
                        >{{ __('products.filters.all_categories') }}</a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a
                                href="{{ route('categories.show', $category->slug) }}"
                                class="category-discovery-choice rounded-2"
                                data-discovery-category="{{ $category->getKey() }}"
                            >{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </x-two-arrow-scroller>
        </nav>

        <div class="category-discovery-results" data-discovery-region>
            <x-loading-state variant="skeleton" :label="__('products.discovery.loading')">
                <x-product-grid :skeleton-count="12" />
            </x-loading-state>
            <div id="category-discovery-results" data-discovery-content aria-live="polite"></div>
            <div class="text-center py-5" data-discovery-error hidden>
                <p role="status">{{ __('products.discovery.error') }}</p>
                <button type="button" class="btn btn-outline theme-secondary rounded-2" data-discovery-retry>
                    {{ __('common.retry') }}
                </button>
            </div>
        </div>
        <div class="text-center mt-4">
            <button
                type="button"
                class="btn btn-outline theme-secondary rounded-2"
                data-discovery-expand
                data-more-label="{{ __('products.discovery.more') }}"
                data-less-label="{{ __('products.discovery.less') }}"
                aria-controls="category-discovery-results"
                aria-expanded="false"
                hidden
            >{{ __('products.discovery.more') }}</button>
        </div>
        <noscript>
            <style>[data-category-discovery] [data-loading-state] { display: none; }</style>
            <p class="text-body-secondary">{{ __('products.discovery.no_script') }}</p>
        </noscript>
    </div>
</section>
