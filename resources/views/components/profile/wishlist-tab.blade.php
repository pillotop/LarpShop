@props(['products'])

<section
    id="pane-wishlist"
    class="tab-pane fade"
    role="tabpanel"
    aria-labelledby="tab-wishlist"
    tabindex="0"
>
    <div class="card border-0 shadow-sm rounded-9 p-4">
        <h2 class="h4">Wishlist</h2>
        <p class="fg-3">Save products for later. Prices and availability may change.</p>

        <div class="d-flex flex-column gap-3">
            @forelse ($products as $product)
                <x-wishlist.product :product="$product" />
            @empty
                <p class="fg-3 mb-0">No saved products are available.</p>
                <a href="{{ route('products.index') }}">Browse products</a>
            @endforelse
        </div>

        @if ($products->hasPages())
            <nav class="mt-4" aria-label="Wishlist pages">
                {{ $products->links('shared.pagination') }}
            </nav>
        @endif
    </div>
</section>
