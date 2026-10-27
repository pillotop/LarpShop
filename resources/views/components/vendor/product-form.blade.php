@props([
    'product' => null,
    'categories' => [],
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Product',
    'allowImageUpload' => false,
])

@php
    $isActive = old('is_active', $product !== null ? (int) $product->is_active : 1);
@endphp

<form action="{{ $action }}" method="POST" @if ($allowImageUpload) enctype="multipart/form-data" @endif>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-4">
        <div class="col-md-8">
            <label for="name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control form-control-lg rounded-3 @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name', $product?->name) }}"
                placeholder="e.g., Hand-Forged Elven Longsword"
                maxlength="150"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="category_id" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
            <select
                class="form-select form-select-lg rounded-3 @error('category_id') is-invalid @enderror"
                id="category_id"
                name="category_id"
                required
            >
                <option value="">-- Select a category --</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->category_id }}" @selected((int) old('category_id', $product?->category_id) === (int) $cat->category_id)>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="price" class="form-label fw-semibold">Unit Price ($) <span class="text-danger">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0.01"
                max="99999999.99"
                class="form-control form-control-lg rounded-3 @error('price') is-invalid @enderror"
                id="price"
                name="price"
                value="{{ old('price', $product?->price) }}"
                placeholder="49.99"
                required
            >
            @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="stock" class="form-label fw-semibold">Available Stock <span class="text-danger">*</span></label>
            <input
                type="number"
                step="1"
                min="0"
                max="1000000"
                class="form-control form-control-lg rounded-3 @error('stock') is-invalid @enderror"
                id="stock"
                name="stock"
                value="{{ old('stock', $product !== null ? $product->stock : 0) }}"
                required
            >
            @error('stock')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Set to 0 to mark the product as out of stock.</div>
        </div>

        <div class="col-md-4">
            <label for="slug" class="form-label fw-semibold">Custom URL Slug (Optional)</label>
            <input
                type="text"
                class="form-control form-control-lg rounded-3 @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                value="{{ old('slug', $product?->slug) }}"
                placeholder="Auto-generated if left blank"
                maxlength="150"
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label fw-semibold">Product Description</label>
            <textarea
                class="form-control rounded-3 @error('description') is-invalid @enderror"
                id="description"
                name="description"
                rows="5"
                maxlength="5000"
                placeholder="Describe materials, dimensions, craftsmanship, and care instructions..."
            >{{ old('description', $product?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <div class="card bg-light border-0 rounded-4 p-3">
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch d-flex align-items-center gap-3 m-0">
                    <input
                        class="form-check-input fs-4 m-0"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked((bool) $isActive)
                    >
                    <div>
                        <label class="form-check-label fw-bold text-dark d-block" for="is_active">
                            Visible in Public Catalog
                        </label>
                        <small class="text-muted">
                            Turn off to hide this product from customers on the storefront and search pages without deleting it.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        @if ($allowImageUpload)
            <div class="col-12">
                <div class="card border rounded-4 p-4">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="bi bi-images text-primary me-1"></i>Initial Product Images (Optional)
                    </h6>
                    <p class="text-muted small mb-3">
                        Upload JPG, PNG, WEBP, or GIF files (up to 5MB each). You can also manage images after creating the product.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <input
                                type="file"
                                class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                                name="images[]"
                                multiple
                                accept="image/png,image/jpeg,image/webp,image/gif"
                            >
                            @error('images')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <input
                                type="text"
                                class="form-control"
                                name="alt_text"
                                value="{{ old('alt_text') }}"
                                placeholder="Image alt text (optional)"
                                maxlength="255"
                            >
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('vendor.products.index') }}" class="btn btn-light rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i>Cancel & Back to Products
            </a>
            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>
</form>
