@props(['product'])

@php
    $image = $product->images->first();
    $path = $image?->path;
    $imageUrl = $path
        ? (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : asset('storage/' . ltrim($path, '/')))
        : asset('images/product-placeholder.jpg');
@endphp

<article class="d-flex flex-wrap align-items-center gap-3 border rounded-3 p-3">
    <img
        src="{{ $imageUrl }}"
        alt="{{ $image?->alt_text ?? $product->name }}"
        width="80"
        height="80"
        loading="lazy"
        class="rounded object-fit-cover"
    >

    <div class="flex-grow-1">
        <a href="{{ route('products.show', [$product, $product->slug]) }}">
            {{ $product->name }}
        </a>
        <p class="mb-1 fw-semibold">${{ number_format($product->price, 2) }}</p>
        <span class="small text-muted">
            {{ $product->stock > 0 ? 'In stock' : 'Out of stock' }}
        </span>
    </div>

    <x-wishlist.button :product="$product" />
</article>
