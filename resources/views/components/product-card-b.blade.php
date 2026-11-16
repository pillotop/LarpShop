@props([
    'product',
])

@php
    $resolveImageUrl = static function (?string $path): ?string {
        if (! $path) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    };

    $firstImage = $product->images->first();
    $imagePath = $resolveImageUrl($firstImage?->path) ?? asset('images/product-placeholder.jpg');
    $imageAlt = $firstImage?->alt_text ?? $product->name;

    $priceParts = explode('.', number_format($product->price ?? 0, 2));
    $dollars = $priceParts[0];
    $cents = $priceParts[1] ?? '00';

    $isTopSeller = ($product->total_reviews ?? 0) >= 2 || (float) ($product->average_rating ?? 0) >= 4.8;
    $inStock = ($product->stock ?? 0) > 0;
@endphp

<div {{ $attributes->merge(['class' => 'card product-card-b border-0 rounded-0 position-relative']) }}>
    <div class="product-card-compare-row z-2 position-relative mb-2">
        <label class="product-card-compare">
            <input
                type="checkbox"
                class="product-card-compare-checkbox"
                value="{{ $product->product_id }}"
                aria-label="{{ __('products.compare') }} {{ $product->name }}"
            >
            <span>{{ __('products.compare') }}</span>
        </label>
    </div>

    <div class="product-card-media position-relative overflow-hidden">
        <a
            href="{{ route('products.show', [
                'product' => $product->product_id,
                'slug' => $product->slug,
            ]) }}"
            class="product-card-link"
            aria-label="{{ $product->name }}"
        >
            @if ($isTopSeller)
                <span class="product-card-badge">{{ __('products.top_seller') }}</span>
            @endif

            <img
                class="product-card-img"
                loading="lazy"
                src="{{ $imagePath }}"
                alt="{{ $imageAlt }}"
            >

        </a>

        <div class="product-card-wishlist z-3">
            <x-wishlist.button
                :product="$product"
                :compact="true"
                class="product-card-wishlist-btn"
            />
        </div>
    </div>

    <div class="card-body px-0 pt-3 pb-2 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="flex-grow-1 min-w-0">
                <a
                    href="{{ route('products.show', [
                        'product' => $product->product_id,
                        'slug' => $product->slug,
                    ]) }}"
                    class="text-decoration-none fg-body d-block"
                >
                    <h5 class="product-card-title text-truncate mb-1">{{ $product->name }}</h5>
                    @if ($product->description || $product->category?->name)
                        <p class="product-card-subtitle mb-2">
                            {{ $product->description ?: $product->category?->name }}
                        </p>
                    @endif
                </a>

                <div class="product-card-price fg-body">
                    <span class="product-card-price-currency">$</span><span class="product-card-price-whole">{{ $dollars }}</span><span class="product-card-price-cents">.{{ $cents }}</span>
                </div>
            </div>

            @if ($inStock)
                <div
                    class="quantity-picker product-card-action z-2 position-relative flex-shrink-0"
                    data-add-to-cart-url="{{ route('cart.add') }}"
                    data-set-cart-url="{{ route('cart.store') }}"
                    data-product-id="{{ $product->product_id }}"
                >
                    @csrf
                    <button
                        id="add-to-cart"
                        class="product-card-cart-btn"
                        type="button"
                        aria-label="{{ __('products.add_to_cart') }}: {{ $product->name }}"
                        title="{{ __('products.add_to_cart') }}"
                    >
                        <i class="bi bi-bag-plus" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ __('products.add_to_cart') }}</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center mt-2">
            <x-rating-container
                average-rating="{{ $product->average_rating }}"
                total-reviews="{{ $product->total_reviews }}"
            />
        </div>

        <div class="product-card-availability mt-2">
            <span
                class="product-card-status-dot {{ $inStock ? 'is-available' : 'is-unavailable' }}"
                aria-hidden="true"
            ></span>
            <span>
                {{ $inStock ? __('products.available_for_delivery') : __('products.out_of_stock') }}
            </span>
        </div>
    </div>
</div>
