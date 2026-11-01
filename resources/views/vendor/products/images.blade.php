@extends('layouts.app')

@section('title', 'Manage Product Images (UC-32) | ' . $product->name)

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
    ])
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.index') }}">Products & Inventory</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.edit', $product->product_id) }}">{{ $product->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Images</li>
            </ol>
        </nav>

        @if (session('success'))
            <div class="alert theme-success alert-dismissible fade show rounded-9 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-lg fg-success"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert theme-danger alert-dismissible fade show rounded-9 p-3 mb-4 shadow-sm" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-9 p-4 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-9 bg-primary bg-10 p-3 fg-primary">
                        <i class="bi bi-images fs-2xl"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold fg-body mb-1">Manage Product Images (UC-32)</h4>
                        <div class="fg-3 small">
                            Product: <strong>{{ $product->name }}</strong> (#{{ $product->product_id }}) &bull;
                            Price: <span class="fg-success fw-bold">${{ number_format((float) $product->price, 2) }}</span> &bull;
                            Stock: <strong>{{ $product->stock }}</strong>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.products.edit', $product->product_id) }}" class="btn btn-outline theme-secondary rounded-pill px-3">
                        <i class="bi bi-pencil me-1"></i>Edit Product
                    </a>
                    @if ($product->is_active && $product->status === 'approved')
                        <a href="{{ route('products.show', [$product->product_id, $product->slug]) }}" target="_blank" class="btn btn-outline theme-primary rounded-pill px-3">
                            <i class="bi bi-eye me-1"></i>View Product Page
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
            <div class="border-bottom pb-3 mb-4">
                <h5 class="fw-bold fg-body mb-1">
                    <i class="bi bi-cloud-arrow-up fg-primary me-2"></i>Upload New Images
                </h5>
                <p class="fg-3 small mb-0">Supported formats: JPG, JPEG, PNG, WEBP, GIF (max 5MB per file). You can select multiple images at once.</p>
            </div>

            <form action="{{ route('vendor.products.images.store', $product->product_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="md:col-7">
                        <label for="images" class="form-label fw-semibold">Image Files <span class="fg-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control form-control-lg rounded-5 @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                            id="images"
                            name="images[]"
                            multiple
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            required
                        >
                        @error('images')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-3">
                        <label for="alt_text" class="form-label fw-semibold">Alt Text</label>
                        <input
                            type="text"
                            class="form-control form-control-lg rounded-5"
                            id="alt_text"
                            name="alt_text"
                            placeholder="Brief image description..."
                        >
                    </div>

                    <div class="md:col-2">
                        <button type="submit" class="btn btn-solid theme-primary btn-lg rounded-pill w-100 fw-semibold">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom pb-3 mb-4">
                <h5 class="fw-bold fg-body mb-0">
                    <i class="bi bi-grid-3x3 fg-primary me-2"></i>Current Gallery ({{ $images->count() }})
                </h5>
                <span class="fg-3 small">The image with the lowest sort order is displayed as the primary cover thumbnail.</span>
            </div>

            @if ($images->isEmpty())
                <div class="text-center py-5 fg-3">
                    <i class="bi bi-image fs-3xl d-block mb-3 fg-secondary"></i>
                    <h6 class="fw-bold">No images uploaded for this product yet</h6>
                    <p class="small fg-3 mb-0">Upload clear photos to showcase your item to customers.</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($images as $index => $img)
                        <x-vendor.image-card
                            :image="$img"
                            :product="$product"
                            :is-primary="$img->sort_order === 1 || ($loop->first && ! $images->contains('sort_order', 1))"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
