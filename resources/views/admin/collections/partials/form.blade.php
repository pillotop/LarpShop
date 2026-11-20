<form
    action="{{ $collection->exists ? route('admin.collections.update', $collection) : route('admin.collections.store') }}"
    method="POST"
    class="border rounded-2 p-4 mb-5"
>
    @csrf
    @if ($collection->exists)
        @method('PUT')
    @endif
    <div class="row g-3">
        <div class="col-12 md:col-6">
            <label for="collection-name" class="form-label">{{ __('promotions.name') }}</label>
            <input
                id="collection-name"
                name="name"
                value="{{ old('name', $collection->name) }}"
                maxlength="150"
                class="form-control rounded-2"
                required
            >
        </div>
        <div class="col-12 md:col-6">
            <label for="collection-slug" class="form-label">{{ __('promotions.slug') }}</label>
            <input
                id="collection-slug"
                name="slug"
                value="{{ old('slug', $collection->slug) }}"
                maxlength="180"
                class="form-control rounded-2"
                aria-describedby="collection-slug-hint"
            >
            <p id="collection-slug-hint" class="small fg-3 mt-2 mb-0">{{ __('promotions.slug_hint') }}</p>
        </div>
        <div class="col-12">
            <label for="collection-description" class="form-label">{{ __('promotions.description') }}</label>
            <textarea
                id="collection-description"
                name="description"
                rows="3"
                maxlength="5000"
                class="form-control rounded-2"
            >{{ old('description', $collection->description) }}</textarea>
        </div>
        <div class="col-12">
            <label class="d-flex gap-2 align-items-center">
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    class="check"
                    @checked(old('is_published', $collection->is_published))
                >
                <span>{{ __('promotions.publish_collection') }}</span>
            </label>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-solid theme-primary rounded-2">{{ __('promotions.save_collection') }}</button>
        <a href="{{ route('admin.collections.index') }}" class="btn btn-outline theme-secondary rounded-2">{{ __('promotions.back') }}</a>
    </div>
</form>
