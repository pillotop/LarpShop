@extends('layouts.app')

@section('title', 'Search Products - LarpShop')

@section('content')
<div class="bg-light min-vh-100">

    <!-- Page header -->
    <header class="bg-white border-bottom shadow-sm">
        <div class="container py-3 d-flex justify-content-between align-items-center">
            <a href="{{ route('home') }}"
               class="text-decoration-none fs-3 fw-bold text-dark">
                Larp<span class="text-primary">Shop</span>
            </a>

            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                <i class="bi bi-house"></i> Home
            </a>
        </div>
    </header>

    <main class="container py-5">

        <!-- Page title -->
        <div class="mb-4">
            <h1 class="fw-bold">Explore Products</h1>
            <p class="text-secondary">
                Discover products that match your needs.
            </p>
        </div>

        <!-- Product search form -->
        <form action="{{ route('products.index') }}"
              method="GET"
              class="card border-0 shadow-sm mb-4">

            <div class="card-body p-4">
                <label for="product-search" class="form-label fw-semibold">
                    Search Products
                </label>

                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>

                    <input
                        id="product-search"
                        type="search"
                        name="q"
                        class="form-control"
                        placeholder="Search by name or keyword..."
                        value="{{ old('q', $keyword ?? '') }}"
                        maxlength="100"
                    >

                    <button type="submit" class="btn btn-primary px-4">
                        Search
                    </button>
                </div>

                @error('q')
                    <p class="text-danger mt-2 mb-0">
                        {{ $message }}
                    </p>
                @enderror

                @if (!empty($keyword))
                    <a href="{{ route('products.index') }}"
                       class="d-inline-block mt-3 text-decoration-none">
                        <i class="bi bi-x-circle"></i> Clear Search
                    </a>
                @endif
            </div>
        </form>

        <!-- Search results summary -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">
                    @if (!empty($keyword))
                        Search Results
                    @else
                        All Products
                    @endif
                </h2>

                @if (!empty($keyword))
                    <p class="text-secondary mb-0">
                        Results for: <strong>{{ $keyword }}</strong>
                    </p>
                @endif
            </div>

            <span class="badge bg-primary-subtle text-primary fs-6">
                {{ $products->count() }}
                {{ $products->count() === 1 ? 'product found' : 'products found' }}
            </span>
        </div>

        <!-- No results -->
        @if ($products->isEmpty())
            <div class="card border-0 shadow-sm text-center p-5">
                <i class="bi bi-search display-3 text-secondary"></i>

                <h3 class="h4 mt-3">No products found</h3>

                <p class="text-secondary">
                    Try searching with a different keyword or browse all products.
                </p>

                <div>
                    <a href="{{ route('products.index') }}"
                       class="btn btn-primary">
                        Browse All Products
                    </a>
                </div>
            </div>
        @else

            <!-- Product grid -->
            <div class="row g-4">
                @foreach ($products as $product)
                    @php
                        $firstImage = $product->images->first();
                        $imagePath = $firstImage?->path;

                        // Support both external URLs and locally stored images.
                        $imageUrl = $imagePath
                            ? (
                                \Illuminate\Support\Str::startsWith(
                                    $imagePath,
                                    ['http://', 'https://']
                                )
                                    ? $imagePath
                                    : asset('storage/' . ltrim($imagePath, '/'))
                            )
                            : null;
                    @endphp

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">

                            <!-- Product image and information -->
                            <a href="{{ route('products.show', [
                                'product' => $product->product_id,
                                'slug' => $product->slug,
                            ]) }}"
                               class="text-decoration-none text-dark">

                                <div class="bg-white d-flex align-items-center justify-content-center"
                                     style="height: 220px;">

                                    @if ($imageUrl)
                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $firstImage->alt_text ?? $product->name }}"
                                            class="w-100 h-100"
                                            style="object-fit: contain;"
                                            loading="lazy"
                                            onerror="this.style.display='none';this.nextElementSibling.classList.remove('d-none');"
                                        >

                                        <div class="d-none text-center text-secondary">
                                            <i class="bi bi-image fs-1"></i>
                                            <p class="small">Image unavailable</p>
                                        </div>
                                    @else
                                        <div class="text-center text-secondary">
                                            <i class="bi bi-image fs-1"></i>
                                            <p class="small">No image available</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="card-body">
                                    <!-- Category -->
                                    <span class="badge bg-light text-secondary border mb-2">
                                        {{ $product->category?->name ?? 'Uncategorized' }}
                                    </span>

                                    <!-- Product name -->
                                    <h3 class="h6 fw-bold mb-2">
                                        {{ $product->name }}
                                    </h3>

                                    <!-- Description -->
                                    <p class="text-secondary small mb-3"
                                       style="min-height: 42px;">
                                        {{ \Illuminate\Support\Str::limit($product->description, 75) }}
                                    </p>

                                    <!-- Price -->
                                    <div class="fw-bold fs-5 text-primary mb-2">
                                        ${{ number_format($product->price, 2) }}
                                    </div>

                                    <!-- Stock availability -->
                                    <div class="small text-secondary">
                                        @if ($product->stock > 0)
                                            <span class="text-success">
                                                <i class="bi bi-check-circle"></i>
                                                In Stock
                                            </span>
                                        @else
                                            <span class="text-danger">
                                                <i class="bi bi-x-circle"></i>
                                                Out of Stock
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>

                            <!-- Product details button -->
                            <div class="card-footer bg-white border-0 pb-3">
                                <a href="{{ route('products.show', [
                                    'product' => $product->product_id,
                                    'slug' => $product->slug,
                                ]) }}"
                                   class="btn btn-outline-primary w-100">
                                    View Details
                                </a>
                            </div>

                        </article>
                    </div>
                @endforeach
            </div>
        @endif

    </main>
</div>
@endsection
