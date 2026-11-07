@props([
    'category' => null,
    'parentCategories' => [],
    'action',
    'method' => 'POST',
    'submitLabel' => __('management.save_category'),
])

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-4">
        <div class="md:col-7">
            <label for="name" class="form-label fw-semibold">{{ __('management.category_name') }} <span class="fg-danger">*</span></label>
            <input
                type="text"
                class="form-control form-control-lg rounded-5 @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name', $category?->name) }}"
                placeholder="{{ __('management.e_g_medieval_armor_cosplay_accessories') }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="md:col-5">
            <label for="parent_id" class="form-label fw-semibold">{{ __('management.parent_category_optional') }}</label>
            <select class="form-control form-control-lg rounded-5 @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                <option value="">{{ __('management.root_category') }}</option>
                @foreach ($parentCategories as $parent)
                    <option value="{{ $parent->category_id }}" @selected(old('parent_id', $category?->parent_id) == $parent->category_id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            @error('parent_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="slug" class="form-label fw-semibold">{{ __('management.url_slug_optional') }}</label>
            <input
                type="text"
                class="form-control rounded-5 @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                value="{{ old('slug', $category?->slug) }}"
                placeholder="{{ __('management.auto_generated_from_name_if_left_blank') }}"
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label fw-semibold">{{ __('management.description') }}</label>
            <textarea
                class="form-control rounded-5 @error('description') is-invalid @enderror"
                id="description"
                name="description"
                rows="4"
                placeholder="{{ __('management.describe_the_products_that_belong_in_this_category') }}"
            >{{ old('description', $category?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('vendor.categories.index') }}" class="btn btn-solid theme-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i>{{ __('management.cancel_back') }}
            </a>
            <button type="submit" class="btn btn-solid theme-primary rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>
</form>
