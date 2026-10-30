@props([
    'category' => null,
    'parentCategories' => [],
    'action',
    'method' => 'POST',
    'submitLabel' => 'Save Category',
])

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-4">
        <div class="col-md-7">
            <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control form-control-lg rounded-3 @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name', $category?->name) }}"
                placeholder="e.g., Medieval Armor, Cosplay Accessories..."
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-5">
            <label for="parent_id" class="form-label fw-semibold">Parent Category (Optional)</label>
            <select class="form-select form-select-lg rounded-3 @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                <option value="">-- Root Category --</option>
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
            <label for="slug" class="form-label fw-semibold">URL Slug (Optional)</label>
            <input
                type="text"
                class="form-control rounded-3 @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                value="{{ old('slug', $category?->slug) }}"
                placeholder="Auto-generated from name if left blank"
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label fw-semibold">Description</label>
            <textarea
                class="form-control rounded-3 @error('description') is-invalid @enderror"
                id="description"
                name="description"
                rows="4"
                placeholder="Describe the products that belong in this category..."
            >{{ old('description', $category?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('vendor.categories.index') }}" class="btn btn-light rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i>Cancel & Back
            </a>
            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>
</form>
