@props([
    'product' => null,
    'categories' => [],
    'action',
    'method' => 'POST',
    'submitLabel' => __('management.save_product'),
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
        <div class="md:col-8">
            <label for="name" class="form-label fw-semibold">{{ __('management.product_name') }} <span class="fg-danger">*</span></label>
            <input
                type="text"
                class="form-control form-control-lg rounded-5 @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name', $product?->name) }}"
                placeholder="{{ __('management.e_g_hand_forged_elven_longsword') }}"
                maxlength="150"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="md:col-4">
            <label for="category_id" class="form-label fw-semibold">{{ __('management.category') }} <span class="fg-danger">*</span></label>
            <select
                class="form-control form-control-lg rounded-5 @error('category_id') is-invalid @enderror"
                id="category_id"
                name="category_id"
                required
            >
                <option value="">{{ __('management.select_a_category') }}</option>
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

        <div class="md:col-4">
            <label for="price" class="form-label fw-semibold">{{ __('management.unit_price_text') }} <span class="fg-danger">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0.01"
                max="99999999.99"
                class="form-control form-control-lg rounded-5 @error('price') is-invalid @enderror"
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

        <div class="md:col-4">
            <label for="stock" class="form-label fw-semibold">{{ __('management.available_stock') }} <span class="fg-danger">*</span></label>
            <input
                type="number"
                step="1"
                min="0"
                max="1000000"
                class="form-control form-control-lg rounded-5 @error('stock') is-invalid @enderror"
                id="stock"
                name="stock"
                value="{{ old('stock', $product !== null ? $product->stock : 0) }}"
                required
            >
            @error('stock')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">{{ __('management.set_to_0_to_mark_the_product_as_out_of_stock') }}</div>
        </div>

        <div class="md:col-4">
            <label for="slug" class="form-label fw-semibold">{{ __('management.custom_url_slug_optional') }}</label>
            <input
                type="text"
                class="form-control form-control-lg rounded-5 @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                value="{{ old('slug', $product?->slug) }}"
                placeholder="{{ __('management.auto_generated_if_left_blank') }}"
                maxlength="150"
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label fw-semibold">{{ __('management.product_description') }}</label>
            <textarea
                class="form-control rounded-5 @error('description') is-invalid @enderror"
                id="description"
                name="description"
                rows="5"
                maxlength="5000"
                placeholder="{{ __('management.describe_materials_dimensions_craftsmanship_and_care_instructions') }}"
            >{{ old('description', $product?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <div class="card bg-1 border-0 rounded-9 p-3">
                <input type="hidden" name="is_active" value="0">
                <div class="d-flex align-items-center gap-2 d-flex align-items-center gap-2 d-flex align-items-center gap-3 m-0">
                    <input
                        class="switch fs-lg m-0"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked((bool) $isActive)
                    >
                    <div>
                        <label class="form-label fw-bold fg-body d-block" for="is_active">
                            {{ __('management.visible_in_public_catalog') }}
                        </label>
                        <small class="fg-3">
                            {{ __('management.products_visibility_help') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        @if ($allowImageUpload)
            <div class="col-12">
                <div class="card border rounded-9 p-4">
                    <h6 class="fw-bold fg-body mb-1">
                        <i class="bi bi-images fg-primary me-1"></i>{{ __('management.initial_product_images_optional') }}
                    </h6>
                    <p class="fg-3 small mb-3">
                        {{ __('management.image_copy.initial_upload_help') }}
                    </p>
                    <div class="row g-3">
                        <div class="md:col-8">
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
                        <div class="md:col-4">
                            <input
                                type="text"
                                class="form-control"
                                name="alt_text"
                                value="{{ old('alt_text') }}"
                                placeholder="{{ __('management.image_alt_text_optional') }}"
                                maxlength="255"
                            >
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('vendor.products.index') }}" class="btn btn-solid theme-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i>{{ __('management.cancel_back_to_products') }}
            </a>
            <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>
</form>
