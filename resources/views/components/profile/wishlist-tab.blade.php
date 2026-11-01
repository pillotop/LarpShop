@props(['products'])

<section
    id="pane-wishlist"
    class="tab-pane fade"
    role="tabpanel"
    aria-labelledby="tab-wishlist"
    tabindex="0"
>
    <div class="card border-0 shadow-sm rounded-9 p-4">
        <h2 class="h4">{{ __('wishlist.title') }}</h2>
        <p class="fg-3">{{ __('wishlist.intro') }}</p>

        <div class="d-flex flex-column gap-3">
            @forelse ($products as $product)
                <x-wishlist.product :product="$product" />
            @empty
                <p class="fg-3 mb-0">{{ __('wishlist.empty') }}</p>
                <a href="{{ route('products.index') }}">{{ __('wishlist.browse') }}</a>
            @endforelse
        </div>

        @if ($products->hasPages())
            <nav class="mt-4" aria-label="{{ __('wishlist.pages') }}">
                {{ $products->links('shared.pagination') }}
            </nav>
        @endif
    </div>
</section>
