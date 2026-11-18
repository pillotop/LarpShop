@props([
    'product',
])

@php
    $firstImage = $product->images->first();
    $storedPath = $firstImage?->path;
    $imagePath = $storedPath
        ? (\Illuminate\Support\Str::startsWith($storedPath, ['http://', 'https://'])
            ? $storedPath
            : asset('storage/' . ltrim($storedPath, '/')))
        : asset('images/product-placeholder.jpg');
    $imageAlt = $firstImage?->alt_text ?? $product->name;
@endphp

<div class="card product-card-a border-0 h-100">
    <a href="{{ route('products.show', [
        'product' => $product->product_id,
        'slug' => $product->slug,
    ]) }}">
        <div class="card-img-top media-frame ratio-1x1 overflow-hidden">
            <img class="object-fit-cover" loading="lazy"
                src="{{ $imagePath }}"
                alt="{{ $imageAlt }}">
        </div>
        <div class="card-body">
            <div class="card-title">
                <h4>{{ $product->name }}</h4>
            </div>
            <div class="card-text text-truncate">
                {{ $product->description }}
            </div>
            <div class="d-flex flex-wrap justify-content-start align-items-baseline">
                <div class="me-1 xl:me-2 fw-bold fg-body">
                    <span class="h2">{{ $money->format($product->price ?? 0) }}</span>
                </div>
                <div class="d-flex align-items-center my-1">
                    <x-rating-container average-rating="{{ $product->average_rating }}" total-reviews="{{ $product->total_reviews }}"/>
                </div>
            </div>
        </div>
    </a>
    <div
        class="card-body-overlay quantity-picker d-flex justify-content-evenly w-100 py-4"
        data-add-to-cart-url="{{ route('cart.add') }}"
        data-set-cart-url="{{ route('cart.store') }}"
        data-product-id="{{ $product->product_id }}"
    >
        @csrf
        <span class="cart-feedback visually-hidden" role="status" aria-live="polite"></span>

        <x-wishlist.button
                :product="$product"
                :compact="true"
            />

        <button
            data-add-to-cart
            aria-label="{{ __('products.add_to_cart') }}"
            class="overlay-btn rounded-circle py-2 px-2"
            type="button"
        >
            <svg class="icon-24"> <use href="/images/icons.svg#cart"></use> </svg>
        </button>
    </div>
</div>
