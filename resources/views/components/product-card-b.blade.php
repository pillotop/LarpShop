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

    $priceParts = explode('.', number_format($product->price ?? 0, 2));
    $dollars = $priceParts[0];
    $cents = $priceParts[1] ?? '00';
@endphp

<div {{ $attributes->merge(['class' => 'card product-card-b border-0 position-relative']) }}>
    <a href="{{ route('products.show', [
        'product' => $product->product_id,
        'slug' => $product->slug,
    ]) }}" class="w-100 h-100 z-1 position-absolute" aria-label="{{ $product->name }}">
    </a>

    <div class="card-img-top ratio ratio-1x1 overflow-hidden">
        <img class="object-fit-cover" loading="lazy"
            src="{{ $imagePath }}"
            alt="{{ $imageAlt }}"
        >
    </div>

    <div class="z-2 position-relative my-2">
        <div class="d-flex gap-2 align-items-center">
            @if (($product->stock ?? 0) > 0)
                <button class="btn btn-sm btn-primary d-flex align-items-center justify-content-center shadow px-2" type="button">
                    Add to cart
                </button>
            @else
                <span class="badge bg-danger d-flex align-items-center justify-content-center px-2 py-1">
                    Out of Stock
                </span>
            @endif
            <button class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center shadow px-2" type="button">
                Options
            </button>
        </div>
    </div>

    <div class="card-body px-0">
        <div class="card-title mt-1">
            <div class="d-flex flex-wrap justify-content-start align-items-baseline">
                <div class="me-1 me-xl-2 fw-bold text-dark">
                    <span class="small" style="vertical-align: 0.65ex; margin-right: 2px;">$</span>
                    <span>{{ $dollars }}</span>
                    <span class="small" style="vertical-align: 0.75ex;">{{ $cents }}</span>
                </div>
            </div>
        </div>
        <div class="card-text">
            <h5 class="h6 text-truncate mb-1">{{ $product->name }}</h5>
            <div class="d-flex align-items-center my-1">
                <x-rating-container average-rating="{{ $product->average_rating }}" total-reviews="{{ $product->total_reviews }}"/>
            </div>
        </div>
    </div>
</div>
